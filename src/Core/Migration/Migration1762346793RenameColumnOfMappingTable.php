<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\ColumnExistsTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1762346793RenameColumnOfMappingTable extends MigrationStep
{
    use ColumnExistsTrait;

    public function getCreationTimestamp(): int
    {
        return 1762346793;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'swag_migration_mapping', 'entity_id')) {
            return;
        }

        if (!$this->columnExists($connection, 'swag_migration_mapping', 'entity_uuid')) {
            return;
        }

        $connection->executeStatement('ALTER TABLE `swag_migration_mapping` CHANGE `entity_uuid` `entity_id` BINARY(16)');
    }
}
