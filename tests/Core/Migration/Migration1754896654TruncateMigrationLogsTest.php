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
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Core\Migration\Migration1754896654TruncateMigrationLogs;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(Migration1754896654TruncateMigrationLogs::class)]
class Migration1754896654TruncateMigrationLogsTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    private const MIGRATION_LOGGING_TABLE = 'swag_migration_logging';

    private const MIGRATION_LOGGING_COUNT = 12;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = KernelLifecycleManager::getConnection();
        $this->addTestLogs();
    }

    public function testShouldTruncateMigrationLogsTable(): void
    {
        static::assertSame(12, $this->getLogCount());

        $migration = new Migration1754896654TruncateMigrationLogs();
        $migration->update($this->connection);
        $migration->update($this->connection);

        static::assertSame(0, $this->getLogCount());
    }

    private function getLogCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ' . self::MIGRATION_LOGGING_TABLE);
    }

    private function addTestLogs(): void
    {
        $runId = Uuid::randomBytes();

        $this->connection->insert('swag_migration_run', [
            'id' => $runId,
            'step' => 'finished',
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        for ($i = 0; $i < self::MIGRATION_LOGGING_COUNT; ++$i) {
            $this->connection->insert(self::MIGRATION_LOGGING_TABLE, [
                'id' => Uuid::randomBytes(),
                'run_id' => $runId,
                'profile_name' => 'profile name',
                'gateway_name' => 'gateway name',
                'level' => 'error',
                'code' => 'code',
                'user_fixable' => 0,
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
