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
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Migration\Run\SwagMigrationAccessTokenService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationProgressService;
use SwagMigrationNext\Migration\Service\MigrationProgressServiceInterface;
use SwagMigrationNext\Migration\Service\ProgressState;
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

    private $credentialFields;

    protected function setUp(): void
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

        $this->credentialFields = [
            'apiUser' => 'testUser',
            'apiKey' => 'testKey',
        ];

        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profileId' => $profileUuidService->getProfileUuid(),
                    'credentialFields' => $this->credentialFields,
                    'totals' => [
                        'toBeFetched' => $this->toBeFetched,
                    ],
                    'additionalData' => $this->additionalData['additionalData'],
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
            $this->profileRepo,
            new SwagMigrationAccessTokenService(
                $this->runRepo,
                $this->profileRepo,
                $this->migrationDataFetcher
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

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->profileId));
        /** @var SwagMigrationProfileEntity $profile */
        $profile = $this->profileRepo->search($criteria, $context)->first();
        $credentialFields = $profile->getCredentialFields();

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

        $expectedEntityGroups = $this->additionalData['additionalData']['entityGroups'];
        foreach ($expectedEntityGroups as &$entityGroup) {
            $entityGroup['progress'] = $entityGroup['count'];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->profileId));
        /** @var SwagMigrationProfileEntity $profile */
        $profile = $this->profileRepo->search($criteria, $context)->first();
        $credentialFields = $profile->getCredentialFields();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->runUuid));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();
        $runCredentialFields = $run->getCredentialFields();

        self::assertSame($progress->getFinishedCount(), 0);
        self::assertTrue($progress->isMigrationRunning());
        self::assertSame($progress->getStatus(), ProgressState::STATUS_WRITE_DATA);
        self::assertSame(
            $progress->getEntityGroups(),
            $expectedEntityGroups
        );
        self::assertSame($runCredentialFields, $credentialFields);
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
        $progress = $this->progressService->getProgress(new Request(), $context);

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
}
