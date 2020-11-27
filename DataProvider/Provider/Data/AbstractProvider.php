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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\Struct;
use SwagMigrationAssistant\DataProvider\Exception\ProviderHasNoTableAccessException;
use SwagMigrationAssistant\DataProvider\Provider\ProviderInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

abstract class AbstractProvider implements ProviderInterface
{
    protected const FORBIDDEN_EXACT_KEYS = ['createdAt', 'updatedAt', 'extensions', 'versionId', '_uniqueIdentifier', 'translated'];
    protected const FORBIDDEN_CONTAINS_KEYS = ['VersionId'];

    public function getProvidedTable(Context $context): array
    {
        throw new ProviderHasNoTableAccessException($this->getIdentifier());
    }

    protected function readTotalFromRepo(EntityRepositoryInterface $repo, Context $context, ?Criteria $criteria = null): int
    {
        if ($criteria === null) {
            $criteria = new Criteria();
        }

        $criteria->addAggregation(new CountAggregation('count', 'id'));

        /** @var CountResult|null $result */
        $result = $repo->aggregate($criteria, $context)->get('count');

        if ($result === null) {
            return 0;
        }

        return $result->getCount();
    }

    protected function readTableFromRepo(EntityRepositoryInterface $repository, Context $context, ?Criteria $criteria = null): array
    {
        if ($criteria === null) {
            $criteria = new Criteria();
        }

        return $repository->search($criteria, $context)->getEntities()->jsonSerialize();
    }

    /**
     * @param entityCollection|array $result
     *
     * cleans up the result and transforms it into an associative array
     */
    protected function cleanupSearchResult($result, array $stripExactKeys = [], array $doNotTouchKeys = []): array
    {
        if ($result instanceof EntityCollection) {
            $cleanResult = \array_values($result->getElements());
        } else {
            $cleanResult = $result;
        }

        foreach ($cleanResult as $key => $value) {
            // cleanup of associative arrays (non integer keys)
            if (\is_string($key) && !\in_array($key, $doNotTouchKeys, true)) {
                // cleanup forbidden keys that match exactly
                if (\in_array($key, self::FORBIDDEN_EXACT_KEYS, true)) {
                    unset($cleanResult[$key]);
                    continue;
                }

                // cleanup keys that were specified as an argument
                if (\in_array($key, $stripExactKeys, true)) {
                    unset($cleanResult[$key]);
                    continue;
                }

                // cleanup forbidden keys that contains the needle
                foreach (self::FORBIDDEN_CONTAINS_KEYS as $forbiddenNeedle) {
                    if (\mb_strpos($key, $forbiddenNeedle)) {
                        unset($cleanResult[$key]);
                        continue;
                    }
                }
            }

            // convert collections & entities to arrays
            if ($value instanceof \JsonSerializable) {
                $cleanResult[$key] = $value->jsonSerialize();
                $value = $cleanResult[$key];
            }

            if (\is_array($value) && !\in_array($key, $doNotTouchKeys, true)) {
                if (empty(\array_filter($value))) {
                    // if all entries of the array equal to FALSE this key will be removed (for example null or '' entries).
                    unset($cleanResult[$key]);
                    continue;
                }

                // cleanup child array
                $cleanResult[$key] = $this->cleanupSearchResult($cleanResult[$key], $stripExactKeys, $doNotTouchKeys);
                continue;
            }

            // remove null value keys
            if ($value === null && !\in_array($key, $doNotTouchKeys, true)) {
                unset($cleanResult[$key]);
                continue;
            }
        }

        return $cleanResult;
    }
}
