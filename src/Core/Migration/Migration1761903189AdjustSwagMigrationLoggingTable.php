<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1761903189AdjustSwagMigrationLoggingTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1761903189;
    }

    public function update(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'swag_migration_logging', 'used_mapping');

        $this->addColumn($connection, 'swag_migration_logging', 'entity_id', 'BINARY(16)');

        if (!$this->indexExists($connection, 'swag_migration_logging', 'idx.entity_id')) {
            $connection->executeStatement('ALTER TABLE `swag_migration_logging` ADD INDEX `idx.entity_id` (`entity_id`);');
        }
    }
}
