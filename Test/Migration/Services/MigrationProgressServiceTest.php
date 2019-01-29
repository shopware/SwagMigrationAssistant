<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Services;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationNext\Migration\Run\EntityProgress;
use SwagMigrationNext\Migration\Run\RunProgress;
use SwagMigrationNext\Migration\Run\RunService;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationProgressService;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationNext\Migration\Service\ProgressState;
use SwagMigrationNext\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Component\HttpFoundation\Request;

class MigrationProgressServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $profileRepo;

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
     * @var string
     */
    private $profileId;

    /**
     * @var MigrationProgressServiceInterface
     */
    private $progressService;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var RunProgress
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

    private $writeArray;

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

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');

        $profileUuidService = new MigrationProfileUuidService(
            $this->profileRepo,
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_TYPE
        );
        $this->profileId = $profileUuidService->getProfileUuid();

        $this->runUuid = Uuid::uuid4()->getHex();
        $this->runProgress = require __DIR__ . '/../../_fixtures/run_progress_data.php';

        $this->credentialFields = [
            'apiUser' => 'testUser',
            'apiKey' => 'testKey',
        ];

        $context->getWriteProtection()->allow('MIGRATION_CONNECTION_CHECK_FOR_RUNNING_MIGRATION');
        $this->connectionId = Uuid::uuid4()->getHex();
        $this->connectionRepo->create(
            [
                [
                    'id' => $this->connectionId,
                    'name' => 'myConnection',
                    'credentialFields' => [
                        'apiUser' => 'testUser',
                        'apiKey' => 'testKey',
                    ],
                    'profileId' => $profileUuidService->getProfileUuid(),
                ],
            ],
            $context
        );
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
            $this->dataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
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
                $this->getContainer()->get(Shopware55MappingService::class),
                $this->getContainer()->get(SwagMigrationAccessTokenService::class),
                new DataSelectionRegistry([]),
                $this->dataRepo,
                $this->mediaFileRepo
            )
        );
    }

    public function testGetProgressFetchInProgress(): void
    {
        $context = Context::createDefaultContext();
        $newCredentialFields = [
            'apiUser' => 'foooo',
            'apiKey' => 'bar',
        ];

        $this->profileRepo->update(
            [
                [
                    'id' => $this->profileId,
                    'credentialFields' => $newCredentialFields,
                ],
            ],
            $context
        );

        $datasets = [];
        $this->initEntity($datasets, CategoryDefinition::getEntityName(), 3, 0);
        $this->dataRepo->create(
            $datasets,
            $context
        );

        $progress = $this->progressService->getProgress(new Request(), $context);

        $credentialFields = $this->connection->getCredentialFields();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->runUuid));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();

        self::assertNotTrue($progress->isMigrationRunning());
        self::assertSame($this->credentialFields, $credentialFields);
        self::assertSame($run->getStatus(), SwagMigrationRunEntity::STATUS_ABORTED);
    }

    public function testGetProgressWriteNotStarted(): void
    {
        $context = Context::createDefaultContext();
        $newCredentialFields = [
            'apiUser' => 'foooo',
            'apiKey' => 'bar',
        ];

        $this->profileRepo->update(
            [
                [
                    'id' => $this->profileId,
                    'credentialFields' => $newCredentialFields,
                ],
            ],
            $context
        );

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

        $this->initAllDatasets($context);
        $progress = $this->progressService->getProgress(new Request(), $context);

        self::assertSame($progress->getFinishedCount(), 0);
        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
        self::assertSame(
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
        $this->initAllDatasets($context);
        $progress = $this->progressService->getProgress(new Request(), $context);

        $runProgress = $this->serializeRunProgressForCompare();
        foreach ($runProgress as &$currentProgress) {
            if ($currentProgress['id'] === 'categories_products') {
                foreach ($currentProgress['entities'] as &$currentEntityProgress) {
                    if ($currentEntityProgress['entityName'] === 'category') {
                        $currentEntityProgress['currentCount'] = $this->writeArray['category']['write'];
                    }
                }
                $currentProgress['currentCount'] = $this->writeArray['category']['write'];
            }
        }
        unset($entityGroup);

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 5);
        self::assertSame($progress->getEntity(), CategoryDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
        self::assertSame(
            $progress->getRunProgress(),
            $runProgress
        );
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
        $this->initAllDatasets($context);
        $progress = $this->progressService->getProgress(new Request(), $context);

        $runProgress = $this->serializeRunProgressForCompare();
        foreach ($runProgress as &$currentProgress) {
            if ($currentProgress['id'] === 'categories_products') {
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

            if ($currentProgress['id'] === 'customers_orders') {
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
        unset($entityGroup);

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 0);
        self::assertSame($progress->getEntity(), OrderDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
        self::assertSame(
            $progress->getRunProgress(),
            $runProgress
        );
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
        $this->initAllDatasets($context);
        $this->insertMediaFiles($context, 23, 0);
        $progress = $this->progressService->getProgress(new Request(), $context);

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 0);
        self::assertSame($progress->getEntity(), MediaDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_DOWNLOAD_DATA);
        self::assertSame(
            $progress->getRunProgress(),
            $this->serializeRunProgressForCompare()
        );
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
        $this->initAllDatasets($context);
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
        unset($entityGroup);

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 20);
        self::assertSame($progress->getRunId(), $this->runUuid);
        self::assertSame($progress->getRunId(), $this->runUuid);
        self::assertSame($progress->getEntity(), MediaDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_DOWNLOAD_DATA);
        self::assertSame(
            $progress->getRunProgress(),
            $runProgress
        );
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
        $this->initAllDatasets($context);
        $this->insertMediaFiles($context, 23, 23);
        $progress = $this->progressService->getProgress(new Request(), $context);

        self::assertFalse($progress->isMigrationRunning());
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
            if ($progress->getId() === 'customers_orders') {
                /** @var EntityProgress $entityProgress */
                foreach ($progress->getEntities() as $entityProgress) {
                    if ($entityProgress->getEntityName() === 'customer') {
                        $entityProgress->setTotal(0);
                    }
                }
                $progress->setTotal(2);
            }
        }
        unset($entityGroup);

        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'progress' => $this->runProgress,
                ],
            ],
            Context::createDefaultContext()
        );

        $this->initAllDatasets($context);
        $this->insertMediaFiles($context, 23, 0);
        $progress = $this->progressService->getProgress(new Request(), $context);

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 0);
        self::assertSame($progress->getEntity(), CategoryDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
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
        self::assertNotTrue($progress->isMigrationRunning());

        $this->runRepo->delete([
            [
                'id' => $this->runUuid,
            ],
        ], $context);

        $progress = $this->progressService->getProgress($request, $context);
        self::assertNotTrue($progress->isMigrationRunning());
    }

    private function initAllDatasets(Context $context): void
    {
        $datasets = [];
        foreach ($this->writeArray as $entityName => $counts) {
            $fetchCount = $counts['fetch'];
            $writeCount = $counts['write'];
            $this->initEntity($datasets, $entityName, $fetchCount, $writeCount);
        }

        $this->dataRepo->create(
            $datasets,
            $context
        );
    }

    private function initEntity(array &$datasets, string $entityName, int $fetchCount, int $writeCount): void
    {
        $currentWriteCount = 0;
        for ($i = 0; $i < $fetchCount; ++$i) {
            $writtenFlag = false;
            if ($currentWriteCount < $writeCount) {
                $writtenFlag = true;
                ++$currentWriteCount;
            }

            $datasets[] = [
                'runId' => $this->runUuid,
                'entity' => $entityName,
                'converted' => [
                    'value' => 'testValue',
                ],
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
        $datasets = [];
        $currentProcessed = 0;
        for ($i = 0; $i < $count; ++$i) {
            $processed = false;
            if ($currentProcessed < $processedCount) {
                $processed = true;
                ++$currentProcessed;
            }

            $datasets[] = [
                'runId' => $this->runUuid,
                'uri' => 'meinMediaFileLink',
                'fileName' => 'myFileName',
                'fileSize' => 5,
                'written' => true,
                'processed' => $processed,
            ];
        }

        $this->mediaFileRepo->create(
            $datasets,
            $context
        );
    }

    private function serializeRunProgressForCompare(): array
    {
        $runProgress = [];
        foreach ($this->runProgress as $currentProgress) {
            $entites = $currentProgress->getEntities();
            $currentProgress->setEntities([]);
            $currentProgress = $currentProgress->jsonSerialize();

            $serializedEntities = [];
            foreach ($entites as $entity) {
                $serializedEntities[] = $entity->jsonSerialize();
            }
            $currentProgress['entities'] = $serializedEntities;

            $runProgress[] = $currentProgress;
        }

        return $runProgress;
    }
}
