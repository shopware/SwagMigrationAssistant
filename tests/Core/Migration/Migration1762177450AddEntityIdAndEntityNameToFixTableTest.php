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
use SwagMigrationAssistant\Core\Migration\Migration1762177450AddEntityIdAndEntityNameToFixTable;
use SwagMigrationAssistant\Test\TableHelperTrait;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1762177450AddEntityIdAndEntityNameToFixTableTest extends TestCase
{
    use TableHelperTrait;

    public function testUpdate(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $this->dropIndex($connection, 'swag_migration_fix', 'idx.entity_id');
        static::assertFalse($this->indexExists($connection, 'swag_migration_fix', 'idx.entity_id'));

        $this->dropColumn($connection, 'swag_migration_fix', 'entity_id');
        static::assertFalse($this->columnExists($connection, 'swag_migration_fix', 'entity_id'));

        $this->dropColumn($connection, 'swag_migration_fix', 'entity_name');
        static::assertFalse($this->columnExists($connection, 'swag_migration_fix', 'entity_name'));

        $migration = new Migration1762177450AddEntityIdAndEntityNameToFixTable();
        $migration->update($connection);
        $migration->update($connection);

        static::assertTrue($this->indexExists($connection, 'swag_migration_fix', 'idx.entity_id'));
        static::assertTrue($this->columnExists($connection, 'swag_migration_fix', 'entity_id'));
        static::assertTrue($this->columnExists($connection, 'swag_migration_fix', 'entity_name'));
    }
}
