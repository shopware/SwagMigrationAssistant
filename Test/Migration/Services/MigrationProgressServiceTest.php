<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Store\Services\StoreService;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\Run\EntityProgress;
use SwagMigrationAssistant\Migration\Run\RunProgress;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationProgressService;
use SwagMigrationAssistant\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationAssistant\Migration\Service\PremappingService;
use SwagMigrationAssistant\Migration\Service\ProgressState;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local\LocalCredentialTrait;
use Symfony\Component\HttpFoundation\Request;

class MigrationProgressServiceTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;
    use LocalCredentialTrait;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $dataRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var MigrationProgressServiceInterface
     */
    private $progressService;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var RunProgress[]
     */
    private $runProgress;

    /**
     * @var array
     */
    private $toBeFetched = [
        'category' => 8,
        'product' => 37,
        'customer' => 3,
        'order' => 2,
        'media' => 23,
    ];

    /**
     * @var array
     */
    private $writeArray;

    /**
     * @var array|null
     */
    private $credentialFields;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $themeRepo;

    protected function setUp(): void
    {
        $this->connectionSetup();

        $context = Context::createDefaultContext();
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $this->themeRepo = $this->getContainer()->get('theme.repository');

        $this->runUuid = Uuid::randomHex();
        $this->runProgress = require __DIR__ . '/../../_fixtures/run_progress_data.php';

        $this->credentialFields = $this->connection->getCredentialFields();

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context): void {
            $this->connectionId = Uuid::randomHex();
            $this->connectionRepo->create(
                [
                    [
                        'id' => $this->connectionId,
                        'name' => 'TestName',
                        'credentialFields' => $this->connection->getCredentialFields(),
                        'profileName' => $this->connection->getProfileName(),
                        'gatewayName' => $this->connection->getGatewayName(),
                    ],
                ],
                $context
            );
        });
        $this->connection = $this->connectionRepo->search(new Criteria([$this->connectionId]), $context)->first();

        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'connectionId' => $this->connectionId,
                    'credentialFields' => $this->credentialFields,
                    'progress' => $this->runProgress,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                ],
            ],
            Context::createDefaultContext()
        );

        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get(EntityWriter::class),
            $this->getContainer()->get(MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo,
            $this->getContainer()->get(SwagMigrationDataDefinition::class),
            $this->getContainer()->get(DataSetRegistry::class),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get(ReaderRegistry::class)
        );

        $this->progressService = new MigrationProgressService(
            $this->runRepo,
            $this->dataRepo,
            $this->mediaFileRepo,
            new SwagMigrationAccessTokenService(
                $this->runRepo
            ),
            new RunService(
                $this->runRepo,
                $this->connectionRepo,
                $this->migrationDataFetcher,
                $this->getContainer()->get(SwagMigrationAccessTokenService::class),
                new DataSelectionRegistry([]),
                $this->dataRepo,
                $this->mediaFileRepo,
                $this->salesChannelRepo,
                $this->themeRepo,
                $this->getContainer()->get(EntityIndexerRegistry::class),
                $this->getContainer()->get(ThemeService::class),
                $this->getContainer()->get(MappingService::class),
                $this->getContainer()->get('cache.object'),
                $this->getContainer()->get(SwagMigrationDataDefinition::class),
                $this->getContainer()->get(Connection::class),
                new LoggingService($this->loggingRepo),
                $this->getContainer()->get(StoreService::class),
                $this->getContainer()->get('messenger.bus.shopware')
            ),
            $this->getContainer()->get(PremappingService::class),
            $this->getContainer()->get(MigrationContextFactory::class)
        );
    }

    public function testGetProgressInPremappingStatusWithValidToken(): void
    {
        $context = Context::createDefaultContext();

        $progress = $this->progressService->getProgress(new Request(), $context);

        $credentialFields = $this->connection->getCredentialFields();
        static::assertSame($this->credentialFields, $credentialFields);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->runUuid));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();
        static::assertSame(SwagMigrationRunEntity::STATUS_ABORTED, $run->getStatus());

        $expectedProgress = [
            'runId' => $this->runUuid,
            'accessToken' => null,
            'migrationRunning' => false,
            'validMigrationRunToken' => true,
            'status' => ProgressState::STATUS_FETCH_DATA,
            'entity' => null,
            'entityCount' => 0,
            'finishedCount' => 0,
        ];

        $encodedProgress = \json_encode($progress);
        static::assertIsNotBool($encodedProgress);

        $progress = \json_decode($encodedProgress, true);
        unset($progress['runProgress'], $progress['extensions']);

        static::assertSame($expectedProgress, $progress);
    }

    public function testGetProgressInPremappingStatusWithInvalidToken(): void
    {
        $context = Context::createDefaultContext();
        // Invalid access token
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'accessToken' => 'random',
                ],
            ],
            $context
        );

        $progress = $this->progressService->getProgress(new Request(), $context);

        $credentialFields = $this->connection->getCredentialFields();
        static::assertSame($this->credentialFields, $credentialFields);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->runUuid));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();
        static::assertSame(SwagMigrationRunEntity::STATUS_RUNNING, $run->getStatus());

        $expectedProgress = [
            'runId' => $this->runUuid,
            'accessToken' => null,
            'migrationRunning' => true,
            'validMigrationRunToken' => false,
            'status' => ProgressState::STATUS_PREMAPPING,
            'entity' => null,
            'entityCount' => 0,
            'finishedCount' => 0,
        ];

        $encodedProgress = \json_encode($progress);
        static::assertIsNotBool($encodedProgress);

        $progress = \json_decode($encodedProgress, true);
        unset($progress['runProgress'], $progress['extensions']);

        static::assertSame($expectedProgress, $progress);
    }

    public function testGetProgressFetchInProgress(): void
    {
        $context = Context::createDefaultContext();
        $entities = [];
        $this->initEntity($entities, DefaultEntities::CATEGORY, 3, 0);
        $this->dataRepo->create(
            $entities,
            $context
        );

        $progress = $this->progressService->getProgress(new Request(), $context);

        $credentialFields = $this->connection->getCredentialFields();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->runUuid));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();

        static::assertTrue($progress->isMigrationRunning());
        static::assertSame($this->credentialFields, $credentialFields);
        static::assertSame(SwagMigrationRunEntity::STATUS_RUNNING, $run->getStatus());
        static::assertSame(ProgressState::STATUS_FETCH_DATA, $progress->getStatus());
    }

    public function testGetProgressWriteNotStarted(): void
    {
        $context = Context::createDefaultContext();

        $this->writeArray = [
            'category' => [
                'fetch' => 8,
                'write' => 0,
            ],
            'product' => [
                'fetch' => 37,
                'write' => 0,
            ],
            'customer' => [
                'fetch' => 3,
                'write' => 0,
            ],
            'order' => [
                'fetch' => 2,
                'write' => 0,
            ],
            'media' => [
                'fetch' => 23,
                'write' => 0,
            ],
        ];

        $this->initAllEntities($context);
        $progress = $this->progressService->getProgress(new Request(), $context);

        static::assertSame(0, $progress->getFinishedCount());
        static::assertTrue($progress->isMigrationRunning());
        static::assertSame(ProgressState::STATUS_WRITE_DATA, $progress->getStatus());
        static::assertSame(
            $progress->getRunProgress(),
            $this->serializeRunProgressForCompare()
        );
    }

    public function testGetProgressWriteStartedWithFirstEntity(): void
    {
        $context = Context::createDefaultContext();

        $this->writeArray = [
            'category' => [
                'fetch' => 8,
                'write' => 5,
            ],
            'product' => [
                'fetch' => 37,
                'write' => 0,
            ],
            'customer' => [
                'fetch' => 3,
                'write' => 0,
            ],
            'order' => [
                'fetch' => 2,
                'write' => 0,
            ],
            'media' => [
                'fetch' => 23,
                'write' => 0,
            ],
        ];

        $this->updateWrittenFlag();
        $this->initAllEntities($context);
        $progress = $this->progressService->getProgress(new Request(), $context);

        $runProgress = $this->serializeRunProgressForCompare();
        foreach ($runProgress as &$currentProgress) {
            if ($currentProgress['id'] === 'products') {
                foreach ($currentProgress['entities'] as &$currentEntityProgress) {
                    if ($currentEntityProgress['entityName'] === 'category') {
                        $currentEntityProgress['currentCount'] = $this->writeArray['category']['write'];
                    }
                }
                $currentProgress['currentCount'] = $this->writeArray['category']['write'];
            }
        }

        static::assertTrue($progress->isMigrationRunning());
        static::assertSame(5, $progress->getFinishedCount());
        static::assertSame(DefaultEntities::CATEGORY, $progress->getEntity());
        static::assertSame(ProgressState::STATUS_WRITE_DATA, $progress->getStatus());
        static::assertSame($runProgress, $progress->getRunProgress());
    }

    public function testGetProgressWriteDoneWithFirstThreeEntities(): void
    {
        $context = Context::createDefaultContext();

        $this->writeArray = [
            'category' => [
                'fetch' => 8,
                'write' => 8,
            ],
            'product' => [
                'fetch' => 37,
                'write' => 37,
            ],
            'customer' => [
                'fetch' => 3,
                'write' => 3,
            ],
            'order' => [
                'fetch' => 2,
                'write' => 0,
            ],
            'media' => [
                'fetch' => 23,
                'write' => 0,
            ],
        ];

        $this->updateWrittenFlag();
        $this->initAllEntities($context);
        $progress = $this->progressService->getProgress(new Request(), $context);

        $runProgress = $this->serializeRunProgressForCompare();
        foreach ($runProgress as &$currentProgress) {
            if ($currentProgress['id'] === 'products') {
                foreach ($currentProgress['entities'] as &$currentEntityProgress) {
                    if ($currentEntityProgress['entityName'] === 'category') {
                        $currentEntityProgress['currentCount'] = $this->writeArray['category']['write'];
                    }

                    if ($currentEntityProgress['entityName'] === 'product') {
                        $currentEntityProgress['currentCount'] = $this->writeArray['product']['write'];
                    }
                }
                $currentProgress['currentCount'] = $this->writeArray['category']['write'] + $this->writeArray['product']['write'];
            }

            if ($currentProgress['id'] === 'customersOrders') {
                foreach ($currentProgress['entities'] as &$currentEntityProgress) {
                    if ($currentEntityProgress['entityName'] === 'customer') {
                        $currentEntityProgress['currentCount'] = $this->writeArray['customer']['write'];
                    }

                    if ($currentEntityProgress['entityName'] === 'order') {
                        $currentEntityProgress['currentCount'] = $this->writeArray['order']['write'];
                    }
                }
                $currentProgress['currentCount'] = $this->writeArray['customer']['write'] + $this->writeArray['order']['write'];
            }
        }

        static::assertTrue($progress->isMigrationRunning());
        static::assertSame(0, $progress->getFinishedCount());
        static::assertSame(DefaultEntities::ORDER, $progress->getEntity());
        static::assertSame(ProgressState::STATUS_WRITE_DATA, $progress->getStatus());
        static::assertSame($runProgress, $progress->getRunProgress());
    }

    public function testGetProgressMediaProcessNotStarted(): void
    {
        $context = Context::createDefaultContext();

        $this->writeArray = [
            'category' => [
                'fetch' => 8,
                'write' => 8,
            ],
            'product' => [
                'fetch' => 37,
                'write' => 37,
            ],
            'customer' => [
                'fetch' => 3,
                'write' => 3,
            ],
            'order' => [
                'fetch' => 2,
                'write' => 2,
            ],
            'media' => [
                'fetch' => 23,
                'write' => 23,
            ],
        ];

        $this->updateWrittenFlag();
        $this->initAllEntities($context);
        $this->insertMediaFiles($context, 23, 0);
        $progress = $this->progressService->getProgress(new Request(), $context);

        static::assertTrue($progress->isMigrationRunning());
        static::assertSame(0, $progress->getFinishedCount());
        static::assertSame(DefaultEntities::MEDIA, $progress->getEntity());
        static::assertSame(ProgressState::STATUS_DOWNLOAD_DATA, $progress->getStatus());
        static::assertSame($this->serializeRunProgressForCompare(), $progress->getRunProgress());
    }

    public function testGetProgressMediaProcessStarted(): void
    {
        $context = Context::createDefaultContext();

        $this->writeArray = [
            'category' => [
                'fetch' => 8,
                'write' => 8,
            ],
            'product' => [
                'fetch' => 37,
                'write' => 37,
            ],
            'customer' => [
                'fetch' => 3,
                'write' => 3,
            ],
            'order' => [
                'fetch' => 2,
                'write' => 2,
            ],
            'media' => [
                'fetch' => 23,
                'write' => 23,
            ],
        ];

        $this->updateWrittenFlag();
        $this->initAllEntities($context);
        $this->insertMediaFiles($context, 23, 20);
        $progress = $this->progressService->getProgress(new Request(), $context);

        $runProgress = $this->serializeRunProgressForCompare();
        foreach ($runProgress as &$currentProgress) {
            if ($currentProgress['id'] === 'processMediaFiles') {
                foreach ($currentProgress['entities'] as &$currentEntityProgress) {
                    if ($currentEntityProgress['entityName'] === 'media') {
                        $currentEntityProgress['currentCount'] = 20;
                    }
                }
                $currentProgress['currentCount'] = 20;
            }
        }

        static::assertTrue($progress->isMigrationRunning());
        static::assertSame(20, $progress->getFinishedCount());
        static::assertSame($progress->getRunId(), $this->runUuid);
        static::assertSame(DefaultEntities::MEDIA, $progress->getEntity());
        static::assertSame(ProgressState::STATUS_DOWNLOAD_DATA, $progress->getStatus());
        static::assertSame($runProgress, $progress->getRunProgress());
    }

    public function testGetProgressMediaProcessDone(): void
    {
        $context = Context::createDefaultContext();

        $this->writeArray = [
            'category' => [
                'fetch' => 8,
                'write' => 8,
            ],
            'product' => [
                'fetch' => 37,
                'write' => 37,
            ],
            'customer' => [
                'fetch' => 3,
                'write' => 3,
            ],
            'order' => [
                'fetch' => 2,
                'write' => 2,
            ],
            'media' => [
                'fetch' => 23,
                'write' => 23,
            ],
        ];

        $this->updateWrittenFlag();
        $this->initAllEntities($context);
        $this->insertMediaFiles($context, 23, 23);
        $progress = $this->progressService->getProgress(new Request(), $context);

        static::assertFalse($progress->isMigrationRunning());
    }

    public function testGetProgressWithEmptyEntityFetchCount(): void
    {
        $context = Context::createDefaultContext();

        $this->writeArray = [
            'category' => [
                'fetch' => 8,
                'write' => 0,
            ],
            'product' => [
                'fetch' => 37,
                'write' => 0,
            ],
            'customer' => [
                'fetch' => 0,
                'write' => 0,
            ],
            'order' => [
                'fetch' => 2,
                'write' => 0,
            ],
            'media' => [
                'fetch' => 23,
                'write' => 0,
            ],
        ];

        /** @var RunProgress $progress */
        foreach ($this->runProgress as $progress) {
            if ($progress->getId() === 'customersOrders') {
                /** @var EntityProgress $entityProgress */
                foreach ($progress->getEntities() as $entityProgress) {
                    if ($entityProgress->getEntityName() === 'customer') {
                        $entityProgress->setTotal(0);
                    }
                }
                $progress->setTotal(2);
            }
        }

        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'progress' => $this->runProgress,
                ],
            ],
            Context::createDefaultContext()
        );

        $this->initAllEntities($context);
        $this->insertMediaFiles($context, 23, 0);
        $progress = $this->progressService->getProgress(new Request(), $context);

        static::assertTrue($progress->isMigrationRunning());
        static::assertSame(0, $progress->getFinishedCount());
        static::assertSame(DefaultEntities::CATEGORY, $progress->getEntity());
        static::assertSame(ProgressState::STATUS_WRITE_DATA, $progress->getStatus());
    }

    public function testGetProgressWithFinishedRun(): void
    {
        $context = Context::createDefaultContext();

        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'status' => SwagMigrationRunEntity::STATUS_FINISHED,
                ],
            ],
            Context::createDefaultContext()
        );

        $request = new Request();
        $progress = $this->progressService->getProgress($request, $context);
        static::assertNotTrue($progress->isMigrationRunning());

        $this->runRepo->delete([
            [
                'id' => $this->runUuid,
            ],
        ], $context);

        $progress = $this->progressService->getProgress($request, $context);
        static::assertNotTrue($progress->isMigrationRunning());
    }

    private function initAllEntities(Context $context): void
    {
        $entities = [];
        foreach ($this->writeArray as $entityName => $counts) {
            $fetchCount = $counts['fetch'];
            $writeCount = $counts['write'];
            $this->initEntity($entities, $entityName, $fetchCount, $writeCount);
        }

        $this->dataRepo->create(
            $entities,
            $context
        );
    }

    private function initEntity(array &$entities, string $entityName, int $fetchCount, int $writeCount): void
    {
        $currentWriteCount = 0;
        for ($i = 0; $i < $fetchCount; ++$i) {
            $writtenFlag = false;
            if ($currentWriteCount < $writeCount) {
                $writtenFlag = true;
                ++$currentWriteCount;
            }

            $entities[] = [
                'runId' => $this->runUuid,
                'entity' => $entityName,
                'converted' => [
                    'value' => 'testValue',
                ],
                'raw' => [],
                'written' => $writtenFlag,
            ];
        }
    }

    private function updateWrittenFlag(): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'totals' => [
                        'toBeFetched' => $this->toBeFetched,
                        'toBeWritten' => $this->toBeFetched,
                    ],
                ],
            ],
            Context::createDefaultContext()
        );
    }

    private function insertMediaFiles(Context $context, int $count, int $processedCount): void
    {
        $entities = [];
        $currentProcessed = 0;
        for ($i = 0; $i < $count; ++$i) {
            $processed = false;
            if ($currentProcessed < $processedCount) {
                $processed = true;
                ++$currentProcessed;
            }

            $entities[] = [
                'runId' => $this->runUuid,
                'entity' => MediaDataSet::getEntity(),
                'uri' => 'meinMediaFileLink',
                'fileName' => 'myFileName',
                'fileSize' => 5,
                'written' => true,
                'processed' => $processed,
            ];
        }

        $this->mediaFileRepo->create(
            $entities,
            $context
        );
    }

    private function serializeRunProgressForCompare(): array
    {
        $runProgress = [];
        foreach ($this->runProgress as $currentProgress) {
            $entities = $currentProgress->getEntities();
            $currentProgress->setEntities([]);
            $currentProgress = $currentProgress->jsonSerialize();

            $serializedEntities = [];
            foreach ($entities as $entity) {
                $serializedEntities[] = $entity->jsonSerialize();
            }
            $currentProgress['entities'] = $serializedEntities;

            $runProgress[] = $currentProgress;
        }

        return $runProgress;
    }
}
