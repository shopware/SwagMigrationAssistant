<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer\MigrationFix;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;

#[Package('after-sales')]
class MigrationFixApplier
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param array<int|string, array<int|string, mixed>> $data
     */
    public function apply(array &$data, string $connectionId): void
    {
        $itemIds = \array_column($data, 'id');
        $mapping = $this->getMapping($itemIds, $connectionId);

        foreach ($data as &$item) {
            $id = $item['id'];

            $mappingWithFixes = $this->getMappingWithFixes($id, $mapping);
            if (!$mappingWithFixes instanceof MigrationFixMapping) {
                continue;
            }

            $mappingWithFixes->applyFixes($item);
        }
    }

    /**
     * @param array<MigrationFixMapping> $mappings
     */
    private function getMappingWithFixes(string $id, array &$mappings): ?MigrationFixMapping
    {
        foreach ($mappings as $index => $fixMapping) {
            if ($id !== $fixMapping->entityUuid) {
                continue;
            }

            if (!$fixMapping->hasFix) {
                continue;
            }

            unset($mappings[$index]);

            return $fixMapping;
        }

        return null;
    }

    /**
     * @param array<string> $ids
     *
     * @return array<MigrationFixMapping>
     */
    private function getMapping(array $ids, string $connectionId): array
    {
        $result = $this->connection->createQueryBuilder()
            ->select('id', 'id', 'connection_id', 'entity', 'old_identifier', 'entity_uuid', 'entity_value', 'checksum', 'additional_data')
            ->from('swag_migration_mapping')
            ->where('entity_uuid IN (:ids)')
            ->andWhere('connection_id = :connectionId')
            ->setParameter('ids', $ids, ArrayParameterType::STRING)
            ->setParameter('connectionId', $connectionId)
            ->executeQuery()
            ->fetchAllAssociativeIndexed();

        $mappingIds = array_keys($result);
        $fixes = $this->getFixes($mappingIds, $connectionId);

        return \array_map(function ($item) use ($fixes) {
            $mapping = MigrationFixMapping::fromDatabaseQuery($item);

            foreach ($fixes as $index => $fix) {
                if ($fix->mainMappingId === $mapping->id) {
                    $mapping->addMigrationFix($fix);
                    unset($fixes[$index]);
                }
            }

            return $mapping;
        }, $result);
    }

    /**
     * @param array<string> $mappingIds
     *
     * @return array<MigrationFix>
     */
    private function getFixes(array $mappingIds, string $connectionId): array
    {
        $result = $this->connection->createQueryBuilder()
            ->select('id', 'connection_id', 'main_mapping_id', 'value', 'path')
            ->from('swag_migration_fixes')
            ->where('main_mapping_id IN (:ids)')
            ->andWhere('connection_id = :connectionId')
            ->setParameter('ids', $mappingIds, ArrayParameterType::STRING)
            ->setParameter('connectionId', $connectionId)
            ->executeQuery()
            ->fetchAllAssociative();

        return \array_map(function ($item) {
            return MigrationFix::fromDatabaseQuery($item);
        }, $result);
    }
}
