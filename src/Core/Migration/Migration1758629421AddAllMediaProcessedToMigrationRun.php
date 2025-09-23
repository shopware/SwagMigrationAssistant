<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1758629421AddAllMediaProcessedToMigrationRun extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1758629421;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'swag_migration_run', 'all_media_processed')) {
            return;
        }

        $connection->executeStatement('
            ALTER TABLE `swag_migration_run` ADD `all_media_processed` TINYINT(1) NULL DEFAULT 0;
        ');
    }
}
