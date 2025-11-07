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
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Core\Migration\Migration1761903189AdjustSwagMigrationLoggingTable;
use SwagMigrationAssistant\Test\TableHelperTrait;

/**
 * @internal
 */
#[Package('after-sales')]
class Migration1761903189AdjustSwagMigrationLoggingTableTest extends TestCase
{
    use KernelTestBehaviour;
    use TableHelperTrait;

    public function testUpdate(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $this->dropIndex($connection, 'swag_migration_logging', 'idx.entity_id');
        static::assertFalse($this->indexExists($connection, 'swag_migration_logging', 'idx.entity_id'));

        $this->dropColumn($connection, 'swag_migration_logging', 'entity_id');
        static::assertFalse($this->columnExists($connection, 'swag_migration_logging', 'entity_id'));

        $this->addColumn($connection, 'swag_migration_logging', 'used_mapping', 'JSON');
        static::assertTrue($this->columnExists($connection, 'swag_migration_logging', 'used_mapping'));

        $migration = new Migration1761903189AdjustSwagMigrationLoggingTable();
        $migration->update($connection);
        $migration->update($connection);

        static::assertTrue($this->indexExists($connection, 'swag_migration_logging', 'idx.entity_id'));
        static::assertTrue($this->columnExists($connection, 'swag_migration_logging', 'entity_id'));
        static::assertFalse($this->columnExists($connection, 'swag_migration_logging', 'used_mapping'));
    }
}
