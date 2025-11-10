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
use SwagMigrationAssistant\Core\Migration\Migration1762436233RemoveMainMappingIdFromMigrationFix;
use SwagMigrationAssistant\Test\TableHelperTrait;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1762436233RemoveMainMappingIdFromMigrationFixTest extends TestCase
{
    use TableHelperTrait;

    public function testUpdate(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $tableName = Migration1762436233RemoveMainMappingIdFromMigrationFix::TABLE_NAME;
        $columnName = Migration1762436233RemoveMainMappingIdFromMigrationFix::COLUMN_NAME;
        $foreignKeyName = Migration1762436233RemoveMainMappingIdFromMigrationFix::FOREIGN_KEY_NAME;

        if (!$this->columnExists($connection, $tableName, $columnName)) {
            $this->addColumn(
                $connection,
                $tableName,
                $columnName,
                'BINARY(16)',
            );

            $this->addForeignKey(
                $connection,
                $tableName,
                $foreignKeyName,
                $columnName,
                'swag_migration_mapping',
                'id',
            );
        }

        static::assertTrue($this->columnExists($connection, $tableName, $columnName));

        $migration = new Migration1762436233RemoveMainMappingIdFromMigrationFix();
        $migration->update($connection);
        $migration->update($connection);

        static::assertFalse($this->columnExists($connection, $tableName, $columnName));
    }
}
