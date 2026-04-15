<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\MessageQueue\Handler\Processor;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\MessageBus\CollectingMessageBus;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\FetchDataSetMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\FetchProcessorMissingLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\MediaProcessingProcessor;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSet;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('fundamentals@after-sales')]
class MediaProcessingProcessorTest extends TestCase
{
    private MediaProcessingProcessor $processor;

    private CollectingMessageBus $bus;

    private MigrationContext $migrationContext;

    private SwagMigrationRunEntity $runEntity;

    private MigrationProgress $progress;

    /**
     * @var array<array<string, mixed>>
     */
    private array $mediaFiles = [];

    private Connection $dbalConnection;

    protected function setUp(): void
    {
        $this->bus = new CollectingMessageBus();

        $this->progress = new MigrationProgress(
            0,
            0,
            new ProgressDataSetCollection([
                'media' => new ProgressDataSet('media', 1000),
            ]),
            'media',
            100
        );

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(Uuid::randomHex());

        $this->runEntity = new SwagMigrationRunEntity();
        $this->runEntity->setId(Uuid::randomHex());
        $this->runEntity->setProgress($this->progress);
        $this->runEntity->setStep(MigrationStep::FETCHING);
        $this->runEntity->setConnection($connection);

        $this->migrationContext = new MigrationContext(
            $connection,
            new Shopware55Profile(),
            null,
            null,
            $this->runEntity->getId()
        );

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturnCallback(fn () => $this->mediaFiles);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setFirstResult')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);

        $this->dbalConnection = $this->createMock(Connection::class);
        $this->dbalConnection->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->processor = $this->createMediaProcessor(
            bus: $this->bus,
            dbalConnection: $this->dbalConnection,
        );
    }

    public function testThrowsExceptionIfNoConnectionIsSet(): void
    {
        $this->runEntity = new SwagMigrationRunEntity();

        try {
            $this->processor->process(
                $this->migrationContext,
                Context::createDefaultContext(),
                $this->runEntity,
                $this->progress
            );
        } catch (\Exception $e) {
            static::assertInstanceOf(MigrationException::class, $e);
            static::assertSame(MigrationException::ENTITY_NOT_EXISTS, $e->getErrorCode());
        }
    }

    public function testTransitionsToNextStepIfNoMediaFiles(): void
    {
        $runTransitionService = $this->createMock(RunTransitionServiceInterface::class);
        $runTransitionService->expects($this->once())
            ->method('transitionToRunStep')
            ->with(
                $this->migrationContext->getRunUuid(),
                MigrationStep::CLEANUP
            );

        $this->processor = $this->createMediaProcessor(
            runTransitionService: $runTransitionService,
            bus: $this->bus,
        );

        $this->processor->process(
            $this->migrationContext,
            Context::createDefaultContext(),
            $this->runEntity,
            $this->progress
        );

        static::assertCount(1, $this->bus->getMessages());
    }

    public function testHandlesDataSetNotFoundExceptionGracefully(): void
    {
        $this->mediaFiles = [
            [
                'id' => Uuid::randomBytes(),
                'run_id' => Uuid::randomBytes(),
                'media_id' => Uuid::randomBytes(),
                'entity' => 'media',
                'written' => 1,
                'file_size' => 10,
            ],
        ];

        $dataSetRegistry = $this->createMock(DataSetRegistry::class);
        $dataSetRegistry->method('getDataSet')->willThrowException(
            MigrationException::dataSetNotFound('unknown')
        );

        $logging = $this->createMock(LoggingService::class);
        $logging->expects($this->once())->method('log')->with(
            static::isInstanceOf(FetchDataSetMissingLog::class)
        );

        $processor = $this->createMediaProcessor(
            bus: $this->bus,
            loggingService: $logging,
            dbalConnection: $this->dbalConnection,
            dataSetRegistry: $dataSetRegistry
        );

        $processor->process(
            $this->migrationContext,
            Context::createDefaultContext(),
            $this->runEntity,
            $this->progress
        );

        static::assertCount(1, $this->bus->getMessages());
    }

    public function testHandlesNoConnectionFoundException(): void
    {
        $processorMock = $this->createMock(MediaFileProcessorInterface::class);
        $processorMock->method('process')->willThrowException(
            MigrationException::noConnectionFound()
        );

        $registry = $this->createMock(MediaFileProcessorRegistryInterface::class);
        $registry->method('getProcessor')->willReturn($processorMock);

        $logging = $this->createMock(LoggingService::class);
        $logging->expects($this->once())->method('log')->with(
            static::isInstanceOf(FetchProcessorMissingLog::class)
        );

        $this->mediaFiles = [
            [
                'id' => Uuid::randomBytes(),
                'run_id' => Uuid::randomBytes(),
                'media_id' => Uuid::randomBytes(),
                'entity' => 'media',
                'written' => 1,
                'file_size' => 10,
            ],
        ];

        $dataSetRegistry = $this->createMock(DataSetRegistry::class);
        $dataSetRegistry->method('getDataSet')->willReturn(new MediaDataSet());

        $processor = $this->createMediaProcessor(
            bus: $this->bus,
            loggingService: $logging,
            dbalConnection: $this->dbalConnection,
            mediaFileProcessorRegistry: $registry,
            dataSetRegistry: $dataSetRegistry
        );

        $processor->process(
            $this->migrationContext,
            Context::createDefaultContext(),
            $this->runEntity,
            $this->progress
        );

        static::assertCount(1, $this->bus->getMessages());
    }

    public function testProcess(): void
    {
        $processorMock = $this->createMock(MediaFileProcessorInterface::class);

        $workload = [
            new MediaProcessWorkloadStruct(
                Uuid::randomHex(),
                Uuid::randomHex(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE,
                [],
                0
            ),
        ];

        $processorMock->expects($this->once())
            ->method('process')
            ->willReturn($workload);

        $processorRegistry = $this->createMock(MediaFileProcessorRegistryInterface::class);
        $processorRegistry->method('getProcessor')->willReturn($processorMock);

        $this->mediaFiles = [
            [
                'id' => Uuid::randomBytes(),
                'run_id' => Uuid::randomBytes(),
                'media_id' => Uuid::randomBytes(),
                'entity' => 'media',
                'written' => 1,
                'file_size' => 10,
            ],
        ];

        $dataSetRegistry = $this->createMock(DataSetRegistry::class);
        $dataSetRegistry->method('getDataSet')->willReturn(new MediaDataSet());

        $processor = $this->createMediaProcessor(
            bus: $this->bus,
            dbalConnection: $this->dbalConnection,
            mediaFileProcessorRegistry: $processorRegistry,
            dataSetRegistry: $dataSetRegistry
        );

        $processor->process(
            $this->migrationContext,
            Context::createDefaultContext(),
            $this->runEntity,
            $this->progress
        );

        static::assertSame(1, $this->progress->getProgress());
        static::assertSame(101, $this->progress->getCurrentEntityProgress());
    }

    public function testProcessRetriesUntilNoErrors(): void
    {
        $processorMock = $this->createMock(MediaFileProcessorInterface::class);

        // First call returns workload with errorCount 1
        // Second call returns workload with errorCount 0
        $firstWorkload = [
            new MediaProcessWorkloadStruct(
                Uuid::randomHex(),
                Uuid::randomHex(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE,
                [],
                1
            ),
        ];
        $secondWorkload = [
            new MediaProcessWorkloadStruct(
                Uuid::randomHex(),
                Uuid::randomHex(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE,
                [],
                0
            ),
        ];

        $processorMock->expects($this->exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls($firstWorkload, $secondWorkload);

        $processorRegistry = $this->createMock(MediaFileProcessorRegistryInterface::class);
        $processorRegistry->method('getProcessor')->willReturn($processorMock);

        $this->mediaFiles = [
            [
                'id' => Uuid::randomBytes(),
                'run_id' => Uuid::randomBytes(),
                'media_id' => Uuid::randomBytes(),
                'entity' => 'media',
                'written' => 1,
                'file_size' => 10,
            ],
        ];

        $dataSetRegistry = $this->createMock(DataSetRegistry::class);
        $dataSetRegistry->method('getDataSet')->willReturn(new MediaDataSet());

        $processor = $this->createMediaProcessor(
            bus: $this->bus,
            dbalConnection: $this->dbalConnection,
            mediaFileProcessorRegistry: $processorRegistry,
            dataSetRegistry: $dataSetRegistry
        );

        $processor->process(
            $this->migrationContext,
            Context::createDefaultContext(),
            $this->runEntity,
            $this->progress
        );

        static::assertSame(1, $this->progress->getProgress());
        static::assertSame(101, $this->progress->getCurrentEntityProgress());
    }

    public function testTransitionsIfAllMediaIsProcessed(): void
    {
        $processorMock = $this->createMock(MediaFileProcessorInterface::class);

        $workload = [
            new MediaProcessWorkloadStruct(
                Uuid::randomHex(),
                Uuid::randomHex(),
                MediaProcessWorkloadStruct::FINISH_STATE,
                [],
                0
            ),
        ];

        $processorMock->expects($this->once())
            ->method('process')
            ->willReturn($workload);

        $processorRegistry = $this->createMock(MediaFileProcessorRegistryInterface::class);
        $processorRegistry->method('getProcessor')->willReturn($processorMock);

        $this->mediaFiles = [
            [
                'id' => Uuid::randomBytes(),
                'run_id' => Uuid::randomBytes(),
                'media_id' => Uuid::randomBytes(),
                'entity' => 'media',
                'written' => 1,
                'file_size' => 10,
            ],
        ];

        $dataSetRegistry = $this->createMock(DataSetRegistry::class);
        $dataSetRegistry->method('getDataSet')->willReturn(new MediaDataSet());

        $runTransitionService = $this->createMock(RunTransitionServiceInterface::class);
        $runTransitionService->expects($this->once())
            ->method('transitionToRunStep')
            ->with(
                $this->migrationContext->getRunUuid(),
                MigrationStep::CLEANUP
            );

        $migrationMediaFileRepository = $this->createMock(EntityRepository::class);
        $migrationMediaFileRepository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationMediaFileEntity::class,
                0,
                new EntityCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $processor = $this->createMediaProcessor(
            migrationDataRepo: $migrationMediaFileRepository,
            runTransitionService: $runTransitionService,
            bus: $this->bus,
            dbalConnection: $this->dbalConnection,
            mediaFileProcessorRegistry: $processorRegistry,
            dataSetRegistry: $dataSetRegistry
        );

        $processor->process(
            $this->migrationContext,
            Context::createDefaultContext(),
            $this->runEntity,
            $this->progress
        );

        static::assertCount(1, $this->bus->getMessages());
        static::assertSame(1, $this->progress->getProgress());
        static::assertSame(101, $this->progress->getCurrentEntityProgress());
    }

    public function testFetchOnlyUnprocessedDataWithoutOffset(): void
    {
        $mediaId = Uuid::randomHex();
        $whereCalls = [];
        $orderByCalls = [];

        $this->mediaFiles = [
            [
                'id' => Uuid::randomBytes(),
                'run_id' => Uuid::randomBytes(),
                'media_id' => Uuid::fromHexToBytes($mediaId),
                'entity' => 'order_document',
                'written' => 1,
                'processed' => 0,
                'process_failure' => 0,
                'file_size' => 0,
            ],
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($this->mediaFiles);

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->never())->method('setFirstResult');
        $queryBuilderMock->method('executeQuery')->willReturn($result);
        $queryBuilderMock->method('select')->willReturnSelf();
        $queryBuilderMock->method('from')->willReturnSelf();
        $queryBuilderMock->method('where')->willReturnSelf();
        $queryBuilderMock->method('setMaxResults')->willReturnSelf();
        $queryBuilderMock->method('setParameter')->willReturnSelf();

        $queryBuilderMock->method('andWhere')->willReturnCallback(static function (string $condition) use (&$whereCalls, $queryBuilderMock) {
            $whereCalls[] = $condition;

            return $queryBuilderMock;
        });
        $queryBuilderMock->method('orderBy')->willReturnCallback(static function (string $sort, ?string $order = null) use (&$orderByCalls, $queryBuilderMock) {
            $orderByCalls[] = [$sort, $order];

            return $queryBuilderMock;
        });

        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->method('createQueryBuilder')->willReturn($queryBuilderMock);

        $processorMock = $this->createMock(MediaFileProcessorInterface::class);
        $processorMock->expects($this->once())
            ->method('process')
            ->willReturn([
                new MediaProcessWorkloadStruct(
                    $mediaId,
                    $this->runEntity->getId(),
                    MediaProcessWorkloadStruct::FINISH_STATE,
                ),
            ]);

        $processorRegistry = $this->createMock(MediaFileProcessorRegistryInterface::class);
        $processorRegistry->method('getProcessor')->willReturn($processorMock);

        $dataSetRegistry = $this->createMock(DataSetRegistry::class);
        $dataSetRegistry->method('getDataSet')->willReturn(new OrderDocumentDataSet());

        $migrationMediaFileRepository = $this->createMock(EntityRepository::class);
        $migrationMediaFileRepository->method('search')->willReturn(
            new EntitySearchResult(
                SwagMigrationMediaFileEntity::class,
                1,
                new EntityCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $processor = $this->createMediaProcessor(
            bus: $this->bus,
            dbalConnection: $dbalConnection,
            mediaFileProcessorRegistry: $processorRegistry,
            dataSetRegistry: $dataSetRegistry,
        );

        $processor->process(
            $this->migrationContext,
            Context::createDefaultContext(),
            $this->runEntity,
            $this->progress
        );

        static::assertContains('written = 1', $whereCalls);
        static::assertContains('processed = 0', $whereCalls);
        static::assertContains('process_failure = 0', $whereCalls);
        static::assertSame([['id, file_size, entity', null]], $orderByCalls);
    }

    /**
     * @param MockObject|EntityRepository<SwagMigrationRunCollection>|null $migrationRunRepo
     * @param MockObject|EntityRepository<SwagMigrationDataCollection>|null $migrationDataRepo
     * @param MockObject|EntityRepository<SwagMigrationMediaFileCollection>|null $migrationMediaFileRepo
     */
    private function createMediaProcessor(
        MockObject|EntityRepository|null $migrationRunRepo = null,
        MockObject|EntityRepository|null $migrationDataRepo = null,
        MockObject|EntityRepository|null $migrationMediaFileRepo = null,
        MockObject|RunTransitionServiceInterface|null $runTransitionService = null,
        MockObject|MessageBusInterface|null $bus = null,
        MockObject|LoggingService|null $loggingService = null,
        MockObject|Connection|null $dbalConnection = null,
        MockObject|MediaFileProcessorRegistryInterface|null $mediaFileProcessorRegistry = null,
        MockObject|DataSetRegistry|null $dataSetRegistry = null,
    ): MediaProcessingProcessor {
        $migrationRunRepo ??= $this->createMock(EntityRepository::class);
        $migrationDataRepo ??= $this->createMock(EntityRepository::class);
        $migrationMediaFileRepo ??= $this->createMock(EntityRepository::class);
        $runTransitionService ??= $this->createMock(RunTransitionServiceInterface::class);
        $bus ??= $this->createMock(MessageBusInterface::class);
        $loggingService ??= $this->createMock(LoggingService::class);
        $dbalConnection ??= $this->createMock(Connection::class);
        $mediaFileProcessorRegistry ??= $this->createMock(MediaFileProcessorRegistryInterface::class);
        $dataSetRegistry ??= $this->createMock(DataSetRegistry::class);

        static::assertInstanceOf(EntityRepository::class, $migrationRunRepo);
        static::assertInstanceOf(EntityRepository::class, $migrationDataRepo);
        static::assertInstanceOf(EntityRepository::class, $migrationMediaFileRepo);
        static::assertInstanceOf(RunTransitionServiceInterface::class, $runTransitionService);
        static::assertInstanceOf(MessageBusInterface::class, $bus);
        static::assertInstanceOf(LoggingService::class, $loggingService);
        static::assertInstanceOf(Connection::class, $dbalConnection);
        static::assertInstanceOf(MediaFileProcessorRegistryInterface::class, $mediaFileProcessorRegistry);
        static::assertInstanceOf(DataSetRegistry::class, $dataSetRegistry);

        return new MediaProcessingProcessor(
            $migrationRunRepo,
            $migrationDataRepo,
            $migrationMediaFileRepo,
            $runTransitionService,
            $bus,
            $loggingService,
            $dbalConnection,
            $mediaFileProcessorRegistry,
            $dataSetRegistry,
            new MigrationConfiguration()
        );
    }
}
