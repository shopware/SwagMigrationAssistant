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
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\MessageBus\CollectingMessageBus;
use Swag\MigrationMagento\Profile\Magento\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Exception\NoConnectionFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\DataSetNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\Log\ProcessorNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor\MediaProcessingProcessor;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSet;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

#[Package('fundamentals@after-sales')]
class MediaProcessingProcessorTest extends TestCase
{
    private MediaProcessingProcessor $processor;

    private CollectingMessageBus $bus;

    private MigrationContext $migrationContext;

    private SwagMigrationRunEntity $runEntity;

    private MigrationProgress $progress;

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

        $this->migrationContext = new MigrationContext(new Shopware55Profile(), $connection, $this->runEntity->getId());

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

        $this->processor = new MediaProcessingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->bus,
            $this->createMock(LoggingService::class),
            $this->dbalConnection,
            $this->createMock(MediaFileProcessorRegistryInterface::class),
            $this->createMock(DataSetRegistry::class),
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
        $runTransitionService->expects(static::once())
            ->method('transitionToRunStep')
            ->with(
                $this->migrationContext->getRunUuid(),
                MigrationStep::CLEANUP
            );

        $this->processor = new MediaProcessingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $runTransitionService,
            $this->bus,
            $this->createMock(LoggingService::class),
            $this->createMock(Connection::class),
            $this->createMock(MediaFileProcessorRegistryInterface::class),
            $this->createMock(DataSetRegistry::class),
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
            new DataSetNotFoundException(400, MigrationException::DATASET_NOT_FOUND, 'unknown')
        );

        $logging = $this->createMock(LoggingService::class);
        $logging->expects(static::once())->method('addLogEntry')->with(
            static::isInstanceOf(DataSetNotFoundLog::class)
        );

        $processor = new MediaProcessingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->bus,
            $logging,
            $this->dbalConnection,
            $this->createMock(MediaFileProcessorRegistryInterface::class),
            $dataSetRegistry
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
            new NoConnectionFoundException(400, MigrationException::DATASET_NOT_FOUND, 'unknown')
        );

        $registry = $this->createMock(MediaFileProcessorRegistryInterface::class);
        $registry->method('getProcessor')->willReturn($processorMock);

        $logging = $this->createMock(LoggingService::class);
        $logging->expects(static::once())->method('addLogEntry')->with(
            static::isInstanceOf(ProcessorNotFoundLog::class)
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

        $processor = new MediaProcessingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->bus,
            $logging,
            $this->dbalConnection,
            $registry,
            $dataSetRegistry
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

        $processorMock->expects(static::once())
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

        $processor = new MediaProcessingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->bus,
            $this->createMock(LoggingService::class),
            $this->dbalConnection,
            $processorRegistry,
            $dataSetRegistry
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

        $processorMock->expects(static::exactly(2))
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

        $processor = new MediaProcessingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(RunTransitionServiceInterface::class),
            $this->bus,
            $this->createMock(LoggingService::class),
            $this->dbalConnection,
            $processorRegistry,
            $dataSetRegistry
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

        $processorMock->expects(static::once())
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
        $runTransitionService->expects(static::once())
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

        $processor = new MediaProcessingProcessor(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $migrationMediaFileRepository,
            $runTransitionService,
            $this->bus,
            $this->createMock(LoggingService::class),
            $this->dbalConnection,
            $processorRegistry,
            $dataSetRegistry
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
}
