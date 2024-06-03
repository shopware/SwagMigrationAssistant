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
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Core\Migration\Migration1717400987UpdateStatusToStepColumnForRun;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;

#[CoversClass(Migration1717400987UpdateStatusToStepColumnForRun::class)]
class Migration1717400987UpdateStatusToStepColumnForRunTest extends TestCase
{
    protected function tearDown(): void
    {
        $conn = KernelLifecycleManager::getConnection();
        $conn->createQueryBuilder()
            ->delete(SwagMigrationRunDefinition::ENTITY_NAME)
            ->executeStatement();
    }

    public function testUpdate(): void
    {
        $conn = KernelLifecycleManager::getConnection();

        // setup database in old (before migration) state
        if ($this->getRunField($conn, 'status') === false) {
            $this->addStatusField($conn);
        }
        if ($this->getRunField($conn, 'step') !== false) {
            $this->removeStepField($conn);
        }

        // add runs with all possible status values
        $runningRunId = Uuid::randomHex();
        $finishedRunId = Uuid::randomHex();
        $abortedRunId = Uuid::randomHex();
        $undefinedRunId = Uuid::randomHex();
        $this->addRun($conn, [
            'id' => Uuid::fromHexToBytes($runningRunId),
            'status' => 'running',
        ]);
        $this->addRun($conn, [
            'id' => Uuid::fromHexToBytes($finishedRunId),
            'status' => 'finished',
        ]);
        $this->addRun($conn, [
            'id' => Uuid::fromHexToBytes($abortedRunId),
            'status' => 'aborted',
        ]);
        $this->addRun($conn, [
            'id' => Uuid::fromHexToBytes($undefinedRunId),
            'status' => 'undefined', // didn't exist in practice
        ]);

        $migration = new Migration1717400987UpdateStatusToStepColumnForRun();
        $migration->update($conn);
        $migration->update($conn);

        // make sure the column was renamed
        $statusField = $this->getRunField($conn, 'status');
        static::assertFalse($statusField);
        $stepField = $this->getRunField($conn, 'step');
        static::assertNotFalse($stepField);

        // compare run steps of previous run status values
        static::assertSame('aborted', $this->getRunStep($conn, $runningRunId));
        static::assertSame('finished', $this->getRunStep($conn, $finishedRunId));
        static::assertSame('aborted', $this->getRunStep($conn, $abortedRunId));
        static::assertSame('aborted', $this->getRunStep($conn, $undefinedRunId));
    }

    /**
     * @return array<string, mixed>|false
     */
    private function getRunField(Connection $conn, string $field): array|false
    {
        return $conn->fetchAssociative('SHOW COLUMNS FROM `swag_migration_run` WHERE `Field`=:field;', [
            'field' => $field,
        ]);
    }

    private function addStatusField(Connection $conn): void
    {
        $conn->executeStatement('
            ALTER TABLE `swag_migration_run` ADD COLUMN `status` VARCHAR(255) NOT NULL;
        ');
    }

    private function removeStepField(Connection $conn): void
    {
        $conn->executeStatement('
            ALTER TABLE `swag_migration_run` DROP COLUMN `step`;
        ');
    }

    /**
     * @param array{id: string, status: string} $run
     */
    private function addRun(Connection $conn, array $run): void
    {
        $conn->createQueryBuilder()
            ->insert(SwagMigrationRunDefinition::ENTITY_NAME)
            ->values([
                'id' => ':id',
                'status' => ':status',
                'created_at' => 'NOW()',
            ])
            ->setParameter('id', $run['id'])
            ->setParameter('status', $run['status'])
            ->executeStatement();
    }

    private function getRunStep(Connection $conn, string $runId): false|string
    {
        return $conn->createQueryBuilder()
            ->select('step')
            ->from(SwagMigrationRunDefinition::ENTITY_NAME)
            ->where('id = :id')
            ->setParameter('id', Uuid::fromHexToBytes($runId))
            ->fetchOne();
    }
}
