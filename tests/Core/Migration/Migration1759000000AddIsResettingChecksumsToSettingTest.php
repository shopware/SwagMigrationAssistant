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
use SwagMigrationAssistant\Core\Migration\Migration1759000000AddIsResettingChecksumsToSetting;

#[Package('fundamentals@after-sales')]
#[CoversClass(Migration1759000000AddIsResettingChecksumsToSetting::class)]
class Migration1759000000AddIsResettingChecksumsToSettingTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testShouldAddIsResettingChecksumsFieldToSettingTable(): void
    {
        static::assertTrue($this->tableExists());

        if ($this->columnExists()) {
            $this->connection->executeStatement(\sprintf(
                'ALTER TABLE %s DROP COLUMN %s',
                Migration1759000000AddIsResettingChecksumsToSetting::TABLE,
                Migration1759000000AddIsResettingChecksumsToSetting::COLUMN
            ));
        }

        $migration = new Migration1759000000AddIsResettingChecksumsToSetting();
        $migration->update($this->connection);
        $migration->update($this->connection);

        static::assertTrue($this->columnExists());
    }

    private function tableExists(): bool
    {
        try {
            $this->connection->fetchOne('SELECT 1 FROM ' . Migration1759000000AddIsResettingChecksumsToSetting::TABLE . ' LIMIT 1');

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function columnExists(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns(Migration1759000000AddIsResettingChecksumsToSetting::TABLE);

        return isset($columns[Migration1759000000AddIsResettingChecksumsToSetting::COLUMN]);
    }
}
