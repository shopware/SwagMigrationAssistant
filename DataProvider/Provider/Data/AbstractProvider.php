<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationAssistant\DataProvider\Provider\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected const FORBIDDEN_EXACT_KEYS = ['createdAt', 'updatedAt', 'extensions', 'versionId', '_uniqueIdentifier', 'translated'];
    protected const FORBIDDEN_CONTAINS_KEYS = ['VersionId'];

    protected function readTotalFromRepo(EntityRepositoryInterface $repo, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addAggregation(new CountAggregation('count', 'id'));

        /** @var CountResult|null $result */
        $result = $repo->aggregate($criteria, $context)->get('count');

        if ($result === null) {
            return 0;
        }

        return $result->getCount();
    }

    /**
     * @param entityCollection|array $result
     *
     * cleans up the result and transforms it into an associative array
     */
    protected function cleanupSearchResult($result): array
    {
        if ($result instanceof EntityCollection) {
            $cleanResult = array_values($result->getElements());
        } else {
            $cleanResult = $result;
        }

        foreach ($cleanResult as $key => $value) {
            // cleanup of associative arrays (non integer keys)
            if (is_string($key)) {
                // cleanup forbidden keys that match exactly
                if (in_array($key, self::FORBIDDEN_EXACT_KEYS, true)) {
                    unset($cleanResult[$key]);
                    continue;
                }

                // cleanup forbidden keys that contains the needle
                foreach (self::FORBIDDEN_CONTAINS_KEYS as $forbiddenNeedle) {
                    if (mb_strpos($key, $forbiddenNeedle)) {
                        unset($cleanResult[$key]);
                        continue;
                    }
                }
            }

            // convert collections to plain arrays
            if ($value instanceof EntityCollection) {
                $cleanResult[$key] = array_values($value->getElements());
                $value = $cleanResult[$key];
            }

            // convert entities to associative arrays
            if ($value instanceof Entity) {
                $cleanResult[$key] = $value->jsonSerialize();
                $value = $cleanResult[$key];
            }

            if (is_array($value)) {
                if (empty(array_filter($value))) {
                    // if all entries of the array equal to FALSE this key will be removed (for example null or '' entries).
                    unset($cleanResult[$key]);
                    continue;
                }

                // cleanup child array
                $cleanResult[$key] = $this->cleanupSearchResult($cleanResult[$key]);
                continue;
            }

            // remove null value keys
            if ($value === null) {
                unset($cleanResult[$key]);
                continue;
            }
        }

        return $cleanResult;
    }
}
