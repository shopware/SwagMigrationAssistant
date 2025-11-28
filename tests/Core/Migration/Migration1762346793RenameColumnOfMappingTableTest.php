<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Core\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use SwagMigrationAssistant\Core\Migration\Migration1762346793RenameColumnOfMappingTable;
use SwagMigrationAssistant\Test\TableHelperTrait;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1762346793RenameColumnOfMappingTableTest extends TestCase
{
    use TableHelperTrait;

    public function testUpdate(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        static::assertTrue($this->columnExists($connection, 'swag_migration_mapping', 'entity_id'));
        $connection->executeStatement('ALTER TABLE `swag_migration_mapping` CHANGE `entity_id` `entity_uuid` BINARY(16)');

        $migration = new Migration1762346793RenameColumnOfMappingTable();
        $migration->update($connection);
        $migration->update($connection);

        static::assertTrue($this->columnExists($connection, 'swag_migration_mapping', 'entity_id'));
        static::assertTrue($this->indexExists($connection, 'swag_migration_mapping', 'idx.swag_migration_mapping.entity_uuid_connection_id'));
    }
}
