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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\DataProvider\Provider\ProviderInterface;
use SwagMigrationAssistant\Exception\MigrationException;

#[Package('services-settings')]
abstract class AbstractProvider implements ProviderInterface
{
    protected const FORBIDDEN_EXACT_KEYS = ['createdAt', 'updatedAt', 'extensions', 'versionId', '_uniqueIdentifier', 'translated'];
    protected const FORBIDDEN_CONTAINS_KEYS = ['VersionId'];

    public function getProvidedTable(Context $context): array
    {
        throw MigrationException::providerHasNoTableAccess($this->getIdentifier());
    }

    protected function readTotalFromRepo(EntityRepository $repo, Context $context, ?Criteria $criteria = null): int
    {
        if ($criteria === null) {
            $criteria = new Criteria();
        }

        $criteria->addAggregation(new CountAggregation('count', 'id'));

        $result = $repo->aggregate($criteria, $context)->get('count');
        if (!$result instanceof CountResult) {
            return 0;
        }

        return $result->getCount();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function readTableFromRepo(EntityRepository $repository, Context $context, ?Criteria $criteria = null): array
    {
        if ($criteria === null) {
            $criteria = new Criteria();
        }

        return \array_values($repository->search($criteria, $context)->getEntities()->jsonSerialize());
    }

    /**
     * cleans up the result and transforms it into an associative array
     *
     * @param EntityCollection|list<Entity>|array<string, mixed> $result
     * @param list<string> $stripExactKeys
     * @param list<string> $doNotTouchKeys
     *
     * @return array<array<string, mixed>>
     */
    protected function cleanupSearchResult(EntityCollection|array $result, array $stripExactKeys = [], array $doNotTouchKeys = []): array
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
                $cleanResult[$key] = $this->cleanupSearchResult($value, $stripExactKeys, $doNotTouchKeys);

                continue;
            }

            // remove null value keys
            if ($value === null && !\in_array($key, $doNotTouchKeys, true)) {
                unset($cleanResult[$key]);
            }
        }

        return $cleanResult;
    }

    /**
     * @param array<string, mixed> $mainEntity
     */
    protected function cleanupAssociationToOnlyContainIds(array &$mainEntity, string $associationName): void
    {
        if (!isset($mainEntity[$associationName])) {
            return;
        }

        $ids = [];
        foreach ($mainEntity[$associationName] as $entity) {
            $ids[] = [
                'id' => $entity['id'],
            ];
        }
        $mainEntity[$associationName] = $ids;
    }
}
