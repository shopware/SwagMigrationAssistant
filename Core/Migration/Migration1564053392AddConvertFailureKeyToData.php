<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1564053392AddConvertFailureKeyToData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1564053392;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE swag_migration_data
ADD KEY IF NOT EXISTS `idx.swag_migration_data.entity__run_id__convert_failure` (`entity`, `run_id`, `convert_failure`);
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
