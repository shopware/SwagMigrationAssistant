<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Core\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Core\Migration\Migration1757598733AddMigrationFixesTable;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(Migration1757598733AddMigrationFixesTable::class)]
class Migration1757598733AddMigrationFixesTableTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testShouldCreateMigrationFixesTable(): void
    {
        if ($this->tableExists()) {
            $this->connection->executeStatement('DROP TABLE ' . Migration1757598733AddMigrationFixesTable::MIGRATION_FIXES_TABLE);
        }

        static::assertFalse($this->tableExists());

        $migration = new Migration1757598733AddMigrationFixesTable();
        $migration->update($this->connection);
        $migration->update($this->connection);

        static::assertTrue($this->tableExists());

        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns(Migration1757598733AddMigrationFixesTable::MIGRATION_FIXES_TABLE);

        static::assertCount(\count(Migration1757598733AddMigrationFixesTable::FIELDS), $columns);

        foreach (Migration1757598733AddMigrationFixesTable::FIELDS as $fieldName => $type) {
            static::assertArrayHasKey($fieldName, $columns);
        }
    }

    private function tableExists(): bool
    {
        try {
            $this->connection->fetchOne('SELECT 1 FROM ' . Migration1757598733AddMigrationFixesTable::MIGRATION_FIXES_TABLE . ' LIMIT 1');

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
