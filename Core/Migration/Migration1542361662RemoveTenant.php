<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\FetchMode;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1542361662RemoveTenant extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1542361662;
    }

    public function update(Connection $connection): void
    {
        $tenantId = null;
        try {
            $tenantId = $connection->executeQuery('SELECT DISTINCT tenant_id FROM language LIMIT 1')->fetch(FetchMode::COLUMN);
        } catch (InvalidFieldNameException $ex) {
            // continue if tenant support has already been removed
        }

        $columns = $this->getColumns();

        $this->removeConstraints($connection);

        foreach ($columns as $table => $columnNames) {
            $instructions = implode(', ', array_map(function (string $name) use ($tenantId) {
                $instruction = 'MODIFY COLUMN `' . $name . '` binary(16) NULL';
                if ($tenantId) {
                    $instruction .= ' DEFAULT :tenantId';
                }

                return $instruction;
            }, $columnNames));

            $sql = <<<SQL
ALTER TABLE $table
DROP PRIMARY KEY,
ADD PRIMARY KEY (`id`),
$instructions
SQL;
            $connection->executeQuery($sql, ['tenantId' => $tenantId]);
        }

        $this->createConstraints($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        $columns = $this->getColumns();

        foreach ($columns as $table => $columnNames) {
            $instructions = implode(', ', array_map(function (string $name) {
                return 'DROP COLUMN `' . $name . '`';
            }, $columnNames));

            $connection->executeQuery('ALTER TABLE `' . $table . '` ' . $instructions);
        }
    }

    private function getColumns(): array
    {
        return [
            'swag_migration_data' => ['tenant_id', 'run_tenant_id'],
            'swag_migration_logging' => ['tenant_id', 'run_tenant_id'],
            'swag_migration_mapping' => ['tenant_id', 'profile_tenant_id'],
            'swag_migration_media_file' => ['tenant_id', 'run_tenant_id'],
            'swag_migration_profile' => ['tenant_id'],
            'swag_migration_run' => ['tenant_id', 'profile_tenant_id'],
        ];
    }

    private function removeConstraints(Connection $connection): void
    {
        $connection->executeQuery('ALTER TABLE `swag_migration_data` DROP FOREIGN KEY `fk_swag_migration_run.run_id`');
        $connection->executeQuery('ALTER TABLE `swag_migration_mapping` DROP FOREIGN KEY `swag_migration_mapping_ibfk_1`');
        $connection->executeQuery('ALTER TABLE `swag_migration_media_file` DROP FOREIGN KEY `fk_swag_migration_file.run_id`');
        $connection->executeQuery('ALTER TABLE `swag_migration_run` DROP FOREIGN KEY `swag_migration_run_ibfk_1`');
    }

    private function createConstraints(Connection $connection): void
    {
        $connection->executeQuery('ALTER TABLE `swag_migration_data` ADD CONSTRAINT `fk_swag_migration_run.run_id` FOREIGN KEY (`run_id`) REFERENCES `swag_migration_run` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE');
        $connection->executeQuery('ALTER TABLE `swag_migration_mapping` ADD FOREIGN KEY (`profile_id`) REFERENCES `swag_migration_profile`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION');
        $connection->executeQuery('ALTER TABLE `swag_migration_media_file` ADD CONSTRAINT `fk_swag_migration_file.run_id` FOREIGN KEY (`run_id`) REFERENCES `swag_migration_run` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE');
        $connection->executeQuery('ALTER TABLE `swag_migration_run` ADD FOREIGN KEY (`profile_id`) REFERENCES `swag_migration_profile`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION');
    }
}
