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
use Shopware\Core\Framework\Uuid\Uuid;

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
        $fixes = $this->getMappings($itemIds, $connectionId);

        foreach ($data as &$item) {
            $id = $item['id'];

            if (!\is_array($fixes[$id])) {
                continue;
            }

            foreach ($fixes[$id] as $fix) {
                $fix->apply($item);
            }
        }

        unset($item);
    }

    /**
     * @param array<int, string> $ids
     *
     * @return array<string, list<MigrationFix>>
     */
    private function getMappings(array $ids, string $connectionId): array
    {
        $sql = <<<'SQL'
SELECT mapping.entity_uuid as entityId, fix.id, fix.value, fix.path FROM swag_migration_mapping as mapping
INNER JOIN swag_migration_fixes as fix ON fix.main_mapping_id = mapping.id
WHERE mapping.entity_uuid IN (:ids)
AND mapping.connection_id = :connectionId
SQL;

        $result = $this->connection->fetchAllAssociative(
            $sql,
            [
                'ids' => Uuid::fromHexToBytesList($ids),
                'connectionId' => Uuid::fromHexToBytes($connectionId),
            ],
            [
                'ids' => ArrayParameterType::STRING,
            ]
        );

        $return = [];
        foreach ($result as $row) {
            $entityUuid = Uuid::fromBytesToHex($row['entityId']);
            if (!\array_key_exists($entityUuid, $return)) {
                $return[$entityUuid] = [];
            }

            $return[$entityUuid][] = MigrationFix::fromDatabaseQuery($row);
        }

        return $return;
    }
}
