<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1568882302AddMappingReferenceToData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1568882302;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_data` ADD `mapping_uuid` binary(16) NULL AFTER `unmapped`; 
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
