<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Services;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunStruct;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationProgressService;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationNext\Migration\Service\ProgressState;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;

class MigrationProgressServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var RepositoryInterface
     */
    private $runRepo;

    /**
     * @var RepositoryInterface
     */
    private $profileRepo;

    /**
     * @var RepositoryInterface
     */
    private $dataRepo;

    /**
     * @var RepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var RepositoryInterface
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
     * @var array
     */
    private $additionalData;

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

    protected function setUp()
    {
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
        $this->additionalData = require __DIR__ . '/../../_fixtures/run_additional_data.php';

        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profileId' => $profileUuidService->getProfileUuid(),
                    'totals' => [
                        'toBeFetched' => $this->toBeFetched,
                    ],
                    'additionalData' => $this->additionalData['additionalData'],
                    'status' => SwagMigrationRunStruct::STATUS_RUNNING,
                ],
            ],
            Context::createDefaultContext()
        );

        $this->progressService = new MigrationProgressService(
            $this->runRepo,
            $this->dataRepo,
            $this->mediaFileRepo
        );

        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->dataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );
    }

    public function testGetProgressFetchInProgress(): void
    {
        $context = Context::createDefaultContext();

        $datasets = [];
        $this->initEntity($datasets, CategoryDefinition::getEntityName(), 3, 0);
        $this->dataRepo->create(
            $datasets,
            $context
        );

        $progress = $this->progressService->getProgress($context);

        self::assertNotTrue($progress->isMigrationRunning());
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

        $this->initAllDatasets($context);
        $progress = $this->progressService->getProgress($context);

        $expectedEntityGroups = $this->additionalData['additionalData']['entityGroups'];
        foreach ($expectedEntityGroups as &$entityGroup) {
            $entityGroup['progress'] = $entityGroup['count'];
        }

        self::assertSame($progress->getFinishedCount(), 0);
        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
        self::assertSame(
            $progress->getEntityGroups(),
            $expectedEntityGroups
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
        $progress = $this->progressService->getProgress($context);

        $expectedEntityGroups = $this->additionalData['additionalData']['entityGroups'];
        foreach ($expectedEntityGroups as &$entityGroup) {
            if ($entityGroup['id'] === 'categories_products') {
                $entityGroup['progress'] = $this->writeArray['category']['write'];
            }
        }

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 5);
        self::assertSame($progress->getEntity(), CategoryDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
        self::assertSame(
            $progress->getEntityGroups(),
            $expectedEntityGroups
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
        $progress = $this->progressService->getProgress($context);

        $expectedEntityGroups = $this->additionalData['additionalData']['entityGroups'];
        foreach ($expectedEntityGroups as &$entityGroup) {
            if ($entityGroup['id'] === 'categories_products') {
                $entityGroup['progress'] = $this->writeArray['category']['write'] + $this->writeArray['product']['write'];
            }

            if ($entityGroup['id'] === 'customers_orders') {
                $entityGroup['progress'] = $this->writeArray['customer']['write'] + $this->writeArray['order']['write'];
            }
        }

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 0);
        self::assertSame($progress->getEntity(), OrderDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
        self::assertSame(
            $progress->getEntityGroups(),
            $expectedEntityGroups
        );
    }

    public function testGetProgressDownloadNotStarted(): void
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
        $progress = $this->progressService->getProgress($context);

        $expectedEntityGroups = $this->additionalData['additionalData']['entityGroups'];
        foreach ($expectedEntityGroups as &$entityGroup) {
            $entityGroup['progress'] = 0;
        }

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 0);
        self::assertSame($progress->getEntity(), MediaDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_DOWNLOAD_DATA);
        self::assertSame(
            $progress->getEntityGroups(),
            $expectedEntityGroups
        );
    }

    public function testGetProgressDownloadStarted(): void
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
        $progress = $this->progressService->getProgress($context);

        $expectedEntityGroups = $this->additionalData['additionalData']['entityGroups'];
        foreach ($expectedEntityGroups as &$entityGroup) {
            $entityGroup['progress'] = 0;
            if ($entityGroup['id'] === 'media') {
                $entityGroup['progress'] = 20;
            }
        }

        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getFinishedCount(), 20);
        self::assertSame($progress->getRunId(), $this->runUuid);
        self::assertSame($progress->getProfile()['id'], $this->profileId);
        self::assertSame($progress->getEntity(), MediaDefinition::getEntityName());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_DOWNLOAD_DATA);
        self::assertSame(
            $progress->getEntityGroups(),
            $expectedEntityGroups
        );
    }

    public function testGetProgressDownloadDone(): void
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
        $progress = $this->progressService->getProgress($context);

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

        $this->toBeFetched['customer'] = 0;
        foreach ($this->additionalData['additionalData']['entityGroups'] as &$entityGroup) {
            if ($entityGroup['id'] === 'customers_orders') {
                $entityGroup['count'] = 2;
                $entityGroup['entities'][0]['entityCount'] = 0;
            }
        }

        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'totals' => [
                        'toBeFetched' => $this->toBeFetched,
                    ],
                    'additionalData' => $this->additionalData['additionalData'],
                ],
            ],
            Context::createDefaultContext()
        );

        $this->initAllDatasets($context);
        $this->insertMediaFiles($context, 23, 0);
        $progress = $this->progressService->getProgress($context);

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
                    'status' => SwagMigrationRunStruct::STATUS_FINISHED,
                ],
            ],
            Context::createDefaultContext()
        );

        $progress = $this->progressService->getProgress($context);
        self::assertNotTrue($progress->isMigrationRunning());

        $this->runRepo->delete([
            [
                'id' => $this->runUuid,
            ],
        ], $context);

        $progress = $this->progressService->getProgress($context);
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

    private function insertMediaFiles(Context $context, int $count, int $downloadedCount): void
    {
        $datasets = [];
        $currentDownloaded = 0;
        for ($i = 0; $i < $count; ++$i) {
            $downloaded = false;
            if ($currentDownloaded < $downloadedCount) {
                $downloaded = true;
                ++$currentDownloaded;
            }

            $datasets[] = [
                'runId' => $this->runUuid,
                'uri' => 'meinMediaFileLink',
                'fileSize' => 5,
                'written' => true,
                'downloaded' => $downloaded,
            ];
        }

        $this->mediaFileRepo->create(
            $datasets,
            $context
        );
    }
}
