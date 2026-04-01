<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertAssociationMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertChildEntityFailedLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\Run\MigrationStep;

#[Package('fundamentals@after-sales')]
class LoggingServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private LoggingService $loggingService;

    /**
     * @var EntityRepository<SwagMigrationLoggingCollection>
     */
    private EntityRepository $loggingRepo;

    private Context $context;

    private string $runUuid;

    private MigrationConfiguration $migrationConfiguration;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $this->migrationConfiguration = new MigrationConfiguration();
        $this->loggingService = new LoggingService($this->loggingRepo, new NullLogger(), $this->migrationConfiguration);

        $runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->runUuid = Uuid::randomHex();
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'status' => 'inProgress',
                    'step' => MigrationStep::FETCHING->value,
                ],
            ],
            $this->context
        );
    }

    public function testAddLogEntry(): void
    {
        $log1 = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))->build(ConvertAssociationMissingLog::class);

        $log2 = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))->build(ConvertChildEntityFailedLog::class);

        $this->loggingService->log($log1);
        $this->loggingService->log($log2);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(0, $result->getTotal());

        $this->loggingService->flush();
        $this->clearCacheData();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(2, $result->getTotal());

        // flush should clear buffer
        $this->loggingService->flush();
        $this->clearCacheData();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(2, $result->getTotal());

        $validCount = 0;
        foreach ($result->getEntities() as $element) {
            if ($log1->getCode() === $element->getCode() || $log2->getCode() === $element->getCode()) {
                ++$validCount;
            }
        }
        static::assertSame(2, $validCount);
    }

    public function testAddLogEntryWithEntityId(): void
    {
        $entityId = Uuid::randomHex();
        $log = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))
            ->withEntityId($entityId)
            ->build(ConvertAssociationMissingLog::class);

        $this->loggingService->log($log);
        $this->loggingService->flush();
        $this->clearCacheData();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(1, $result->getTotal());
        $resultLog = $result->getEntities()->first();
        static::assertInstanceOf(SwagMigrationLoggingEntity::class, $resultLog);
        static::assertSame($entityId, $resultLog->getEntityId());
    }

    public function testDeconstructLoggingServiceFlushesBuffer(): void
    {
        $log = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))->build(ConvertAssociationMissingLog::class);

        $loggingService = new LoggingService($this->loggingRepo, new NullLogger(), new MigrationConfiguration());
        $loggingService->log($log);
        unset($loggingService);

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(1, $result->getTotal());
    }

    public function testResetFlushesBuffer(): void
    {
        $log = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))->build(ConvertAssociationMissingLog::class);

        $this->loggingService->log($log);
        $this->loggingService->reset();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(1, $result->getTotal());
    }

    public function testBufferOverflowFlushesBuffer(): void
    {
        for ($i = 0; $i < $this->migrationConfiguration->migrationLogBufferSize + 10; ++$i) {
            $log = (new MigrationLogBuilder(
                $this->runUuid,
                'Profile name',
                'Gateway name',
                Uuid::randomHex(),
            ))->build(ConvertAssociationMissingLog::class);

            $this->loggingService->log($log);
        }

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame($this->migrationConfiguration->migrationLogBufferSize, $result->getTotal());

        $this->loggingService->flush();
        $this->clearCacheData();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame($this->migrationConfiguration->migrationLogBufferSize + 10, $result->getTotal());
    }

    public function testLimitExceptionTrace(): void
    {
        $trace = [];

        for ($i = 0; $i < $this->migrationConfiguration->migrationLogExceptionTraceItemLimit + 5; ++$i) {
            $trace[] = [
                'file' => __FILE__,
                'type' => '->',
                'args' => [],
            ];
        }

        $log = (new MigrationLogBuilder(
            $this->runUuid,
            'Profile name',
            'Gateway name',
            Uuid::randomHex(),
        ))
            ->withExceptionTrace($trace)
            ->build(ConvertAssociationMissingLog::class);

        $this->loggingService->log($log);
        $this->loggingService->flush();

        $result = $this->loggingRepo->search(new Criteria(), $this->context);
        static::assertSame(1, $result->getTotal());

        $resultLog = $result->getEntities()->first();
        static::assertInstanceOf(SwagMigrationLoggingEntity::class, $resultLog);

        $resultTrace = $resultLog->getExceptionTrace();
        static::assertIsArray($resultTrace);

        static::assertCount($this->migrationConfiguration->migrationLogExceptionTraceItemLimit, $resultTrace);
    }
}
