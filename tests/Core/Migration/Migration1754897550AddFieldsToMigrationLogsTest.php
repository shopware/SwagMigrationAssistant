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
use SwagMigrationAssistant\Core\Migration\Migration1754897550AddFieldsToMigrationLogs;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(Migration1754897550AddFieldsToMigrationLogs::class)]
class Migration1754897550AddFieldsToMigrationLogsTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testShouldAddFieldsToMigrationLogsTable(): void
    {
        static::assertTrue($this->tableExists());

        $migration = new Migration1754897550AddFieldsToMigrationLogs();
        $migration->update($this->connection);
        $migration->update($this->connection);

        static::assertTrue($this->tableExists());

        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns(Migration1754897550AddFieldsToMigrationLogs::MIGRATION_LOGGING_TABLE);

        $fields = array_keys(
            array_merge(
                Migration1754897550AddFieldsToMigrationLogs::REQUIRED_FIELDS,
                Migration1754897550AddFieldsToMigrationLogs::OPTIONAL_FIELDS,
                Migration1754897550AddFieldsToMigrationLogs::SYSTEM_FIELDS
            )
        );

        foreach ($fields as $fieldName) {
            static::assertArrayHasKey($fieldName, $columns);
        }

        foreach (Migration1754897550AddFieldsToMigrationLogs::FIELDS_TO_DROP as $fieldName) {
            static::assertArrayNotHasKey($fieldName, $columns);
        }
    }

    private function tableExists(): bool
    {
        try {
            $this->connection->fetchOne('SELECT 1 FROM ' . Migration1754897550AddFieldsToMigrationLogs::MIGRATION_LOGGING_TABLE . ' LIMIT 1');

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
