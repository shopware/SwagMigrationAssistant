<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1544512973AddEnvironmentInformationToRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1544512973;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_run` ADD `environment_information` LONGTEXT AFTER `totals`;
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
