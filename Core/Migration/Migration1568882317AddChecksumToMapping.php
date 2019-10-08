<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1568882317AddChecksumToMapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1568882317;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_mapping` ADD `checksum` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL AFTER `entity_value`; 
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
