<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\ErrorResolution;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('fundamentals@after-sales')]
class SwagMigrationErrorResolutionService
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param array<int|string, array<int|string, mixed>> $data
     */
    public function apply(array &$data, string $connectionId, string $runId): void
    {
        $itemIds = \array_column($data, 'id');
        $fixes = $this->getFixes($itemIds, $connectionId, $runId);

        foreach ($data as &$item) {
            $id = $item['id'];

            if (!\array_key_exists($id, $fixes) || !\is_array($fixes[$id])) {
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
     * @return SwagMigrationErrorResolution
     */
    private function getFixes(array $ids, string $connectionId, string $runId): array
    {
        // To ensure, only select fixes for the current run, join swag_migration_logging table and filter by run_id
        $sql = <<<'SQL'
SELECT fix.entity_id AS entityId, fix.id, fix.value, fix.path
FROM swag_migration_fix AS fix
INNER JOIN swag_migration_logging AS log ON log.entity_id = fix.entity_id
WHERE fix.entity_id IN (:ids)
AND fix.connection_id = :connectionId
AND log.run_id = :runId
AND log.user_fixable = 1;
SQL;

        $result = $this->connection->fetchAllAssociative(
            $sql,
            [
                'ids' => Uuid::fromHexToBytesList($ids),
                'connectionId' => Uuid::fromHexToBytes($connectionId),
                'runId' => Uuid::fromHexToBytes($runId),
            ],
            [
                'ids' => ArrayParameterType::STRING,
            ]
        );

        $return = [];
        foreach ($result as $row) {
            $entityId = Uuid::fromBytesToHex($row['entityId']);

            if (!\array_key_exists($entityId, $return)) {
                $return[$entityId] = [];
            }

            $return[$entityId][] = SwagMigrationErrorResolution::fromDatabaseQuery($row);
        }

        return $return;
    }
}
