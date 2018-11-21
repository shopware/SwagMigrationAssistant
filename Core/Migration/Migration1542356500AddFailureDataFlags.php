<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1542356500AddFailureDataFlags extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1542356500;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_data` ADD `convert_failure` TINYINT(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT FALSE;
SQL;
        $connection->exec($sql);

        $sql = <<<SQL
ALTER TABLE `swag_migration_data` ADD `write_failure` TINYINT(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT FALSE;
SQL;
        $connection->exec($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
