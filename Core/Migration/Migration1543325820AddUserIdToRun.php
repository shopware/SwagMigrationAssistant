<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1543325820AddUserIdToRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1543325820;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `user_id` VARCHAR(255) COLLATE utf8mb4_unicode_ci AFTER `additional_data`;
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
