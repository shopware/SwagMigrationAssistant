<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Core\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Core\Migration\Migration1758526242MigrationFixesTable;

#[CoversClass(Migration1758526242MigrationFixesTable::class)]
class Migration1758526242MigrationFixesTableTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testUpdate(): void
    {
        $this->dropTable();
        static::assertFalse($this->tableExists());

        $migration = new Migration1758526242MigrationFixesTable();
        $migration->update($this->connection);
        $migration->update($this->connection);

        static::assertTrue($this->tableExists());
    }

    private function dropTable(): void
    {
        $this->connection->executeStatement('DROP TABLE IF EXISTS `swag_migration_fixes`');
    }

    private function tableExists(): bool
    {
        return $this->connection->createSchemaManager()->tablesExist(['swag_migration_fixes']);
    }
}
