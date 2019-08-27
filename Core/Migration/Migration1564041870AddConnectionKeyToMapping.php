<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1564041870AddConnectionKeyToMapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1564041870;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE swag_migration_mapping
ADD KEY IF NOT EXISTS `idx.swag_migration_mapping.connection_id_entity_old_identifier` (`connection_id`, `entity`, `old_identifier`);
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
