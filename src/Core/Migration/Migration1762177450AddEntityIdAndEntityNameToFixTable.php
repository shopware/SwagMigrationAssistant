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
class Migration1762177450AddEntityIdAndEntityNameToFixTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1762177450;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn($connection, 'swag_migration_fix', 'entity_id', 'BINARY(16)');
        $this->addColumn($connection, 'swag_migration_fix', 'entity_name', 'VARCHAR(255)');

        if (!$this->indexExists($connection, 'swag_migration_fix', 'idx.entity_id')) {
            $connection->executeStatement('ALTER TABLE `swag_migration_fix` ADD INDEX `idx.entity_id` (`entity_id`);');
        }
    }
}
