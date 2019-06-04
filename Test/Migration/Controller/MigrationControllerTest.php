<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Migration\Controller;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\IndexerRegistryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Controller\MigrationController;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Exception\MigrationRunUndefinedStatusException;
use SwagMigrationAssistant\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistry;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriter;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyHttpMediaDownloadService;
use SwagMigrationAssistant\Test\Mock\Migration\Service\DummyMediaFileProcessorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MigrationControllerTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;

    /**
     * @var MigrationController
     */
    private $controller;

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $generalSettingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $profileRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepo;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    /**
     * @var string
     */
    private $invalidRunUuid;

    /**
     * @var EntityRepositoryInterface
     */
    private $dataRepo;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $dataDefinition = $this->getContainer()->get(SwagMigrationDataDefinition::class);
        $this->mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $this->dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $currencyRepo = $this->getContainer()->get('currency.repository');
        $this->profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->profileUuidService = new MigrationProfileUuidService($this->profileRepo, Shopware55Profile::PROFILE_NAME, Shopware55LocalGateway::GATEWAY_NAME);
        $this->generalSettingRepo = $this->getContainer()->get('swag_migration_general_setting.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->dataSetRegistry = $this->getContainer()->get(DataSetRegistry::class);

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) {
            $this->connectionId = Uuid::randomHex();
            $this->connectionRepo->create(
                [
                    [
                        'id' => $this->connectionId,
                        'name' => 'myConnection',
                        'credentialFields' => [
                            'endpoint' => 'testEndpoint',
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileId' => $this->profileUuidService->getProfileUuid(),
                    ],
                ],
                $context
            );
        });

        $this->runUuid = Uuid::randomHex();
        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'connectionId' => $this->connectionId,
                    'progress' => require __DIR__ . '/../../_fixtures/run_progress_data.php',
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'accessToken' => 'testToken',
                ],
            ],
            Context::createDefaultContext()
        );

        $this->invalidRunUuid = Uuid::randomHex();
        $this->runRepo->create(
            [
                [
                    'id' => $this->invalidRunUuid,
                    'progress' => require __DIR__ . '/../../_fixtures/run_progress_data.php',
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'accessToken' => 'testToken',
                ],
            ],
            Context::createDefaultContext()
        );

        $mappingService = $this->getContainer()->get(MappingService::class);
        $accessTokenService = new SwagMigrationAccessTokenService(
            $this->runRepo
        );
        $dataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get(EntityWriter::class),
            $mappingService,
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get('swag_migration_logging.repository'),
            $dataDefinition
        );
        $this->controller = new MigrationController(
            $dataFetcher,
            $this->getContainer()->get(MigrationDataWriter::class),
            new DummyMediaFileProcessorService(
                $this->mediaFileRepo,
                $this->getContainer()->get('messenger.bus.shopware')
            ),
            $accessTokenService,
            new RunService(
                $this->runRepo,
                $this->connectionRepo,
                $dataFetcher,
                $accessTokenService,
                new DataSelectionRegistry([]),
                $this->dataRepo,
                $this->mediaFileRepo,
                $currencyRepo,
                $this->getContainer()->get(IndexerRegistryInterface::class),
                $this->getContainer()->get('shopware.cache'),
                $dataDefinition,
                $this->getContainer()->get(Connection::class)
            ),
            $this->runRepo,
            $this->dataSetRegistry
        );
    }

    public function testRunUndefinedStatus(): void
    {
        try {
            $run = new SwagMigrationRunEntity();
            $run->setStatus('invalidRunStatus');
        } catch (\Exception $e) {
            /* @var MigrationRunUndefinedStatusException $e */
            static::assertInstanceOf(MigrationRunUndefinedStatusException::class, $e);
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            static::assertArrayHasKey('status', $e->getParameters());
            static::assertSame('invalidRunStatus', $e->getParameters()['status']);
        }
    }

    public function testWriteDataWithInvalidRunId(): void
    {
        $context = Context::createDefaultContext();
        $params = [
            'runUuid' => Uuid::randomHex(),
            'entity' => ProductDataSet::getEntity(),
        ];

        $request = new Request([], $params);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->writeData($request, $context);
    }

    public function testFetchData(): void
    {
        $context = Context::createDefaultContext();

        $params = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDataSet::getEntity(),
        ];
        $request = new Request([], $params);
        $result = $this->controller->fetchData($request, $context);

        static::assertSame(['validToken' => false], json_decode($result->getContent(), true));
        static::assertSame(Response::HTTP_OK, $result->getStatusCode());

        $params[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = 'testToken';
        $request = new Request([], $params);
        $result = $this->controller->fetchData($request, $context);

        static::assertSame(['validToken' => true], json_decode($result->getContent(), true));
        static::assertSame(Response::HTTP_OK, $result->getStatusCode());
    }

    public function testFetchDataWithIncorrectRunUuid(): void
    {
        $context = Context::createDefaultContext();

        $params = [
            'runUuid' => Uuid::randomHex(),
            'entity' => ProductDataSet::getEntity(),
            SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME => 'testToken',
        ];
        $request = new Request([], $params);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->fetchData($request, $context);
    }

    public function testFetchDataWithIncorrectConnection(): void
    {
        $context = Context::createDefaultContext();

        $params = [
            'runUuid' => $this->invalidRunUuid,
            'entity' => ProductDataSet::getEntity(),
            SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME => 'testToken',
        ];
        $request = new Request([], $params);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->fetchData($request, $context);
    }

    public function requiredFetchDataProperties(): array
    {
        return [
            ['runUuid'],
            ['entity'],
        ];
    }

    /**
     * @dataProvider requiredFetchDataProperties
     */
    public function testFetchDataWithMissingProperty(string $missingProperty): void
    {
        $context = Context::createDefaultContext();

        $properties = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDataSet::getEntity(),
        ];
        unset($properties[$missingProperty]);

        $request = new Request([], $properties);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->fetchData($request, $context);
    }

    public function testUpdateWriteProgress(): void
    {
        $context = Context::createDefaultContext();

        $properties = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDataSet::getEntity(),
        ];
        $request = new Request([], $properties);

        $this->createDataRows(DefaultEntities::CATEGORY, 10);
        $this->createDataRows(DefaultEntities::PRODUCT, 11);
        $this->createDataRows(DefaultEntities::CUSTOMER, 12);
        $this->createDataRows(DefaultEntities::ORDER, 13);
        $this->createDataRows(DefaultEntities::MEDIA, 14);

        $result = $this->controller->updateWriteProgress($request, $context);
        $progress = json_decode($result->getContent(), true);

        static::assertSame(10, $progress[0]['entities'][0]['total']);
        static::assertSame(11, $progress[0]['entities'][1]['total']);
        static::assertSame(21, $progress[0]['total']);

        static::assertSame(12, $progress[1]['entities'][0]['total']);
        static::assertSame(13, $progress[1]['entities'][1]['total']);
        static::assertSame(25, $progress[1]['total']);

        static::assertSame(14, $progress[2]['entities'][0]['total']);
        static::assertSame(14, $progress[2]['total']);
    }

    public function testUpdateWriteProgressWithoutRunUuid(): void
    {
        $properties = [
            'entity' => ProductDataSet::getEntity(),
        ];
        $request = new Request([], $properties);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->updateWriteProgress($request, $this->context);
    }

    public function testUpdateWriteProgressWithInvalidRunUuid(): void
    {
        $properties = [
            'runUuid' => Uuid::randomHex(),
            'entity' => ProductDataSet::getEntity(),
        ];
        $request = new Request([], $properties);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->updateWriteProgress($request, $this->context);
    }

    public function testUpdateMediaFilesProgress(): void
    {
        $context = Context::createDefaultContext();

        $properties = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDataSet::getEntity(),
        ];
        $request = new Request([], $properties);

        $this->createMediaFileRows(14, true, true);
        $this->createMediaFileRows(10, true);

        $result = $this->controller->updateMediaFilesProgress($request, $context);
        $progress = json_decode($result->getContent(), true);

        static::assertSame(14, $progress[0]['entities'][0]['currentCount']);
        static::assertSame(24, $progress[0]['entities'][0]['total']);
        static::assertSame(14, $progress[0]['currentCount']);
        static::assertSame(24, $progress[0]['total']);
    }

    public function testUpdateMediaFilesProgressWithoutRunUuid(): void
    {
        $properties = [
            'entity' => ProductDataSet::getEntity(),
        ];
        $request = new Request([], $properties);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->updateMediaFilesProgress($request, $this->context);
    }

    public function testUpdateMediaFilesProgressWithInvalidRunUuid(): void
    {
        $properties = [
            'runUuid' => Uuid::randomHex(),
            'entity' => ProductDataSet::getEntity(),
        ];
        $request = new Request([], $properties);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->updateMediaFilesProgress($request, $this->context);
    }

    public function requiredWriteDataProperties(): array
    {
        return [
            ['runUuid'],
            ['entity'],
        ];
    }

    /**
     * @dataProvider requiredWriteDataProperties
     */
    public function testWriteDataWithMissingProperty(string $missingProperty): void
    {
        $properties = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDataSet::getEntity(),
        ];
        unset($properties[$missingProperty]);

        $request = new Request([], $properties);
        $context = Context::createDefaultContext();

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->writeData($request, $context);
    }

    public function testWriteData(): void
    {
        $params = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDataSet::getEntity(),
        ];
        $context = Context::createDefaultContext();

        $request = new Request([], $params);
        $result = $this->controller->writeData($request, $context);

        static::assertSame(['validToken' => false], json_decode($result->getContent(), true));
        static::assertSame(Response::HTTP_OK, $result->getStatusCode());

        $params[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = 'testToken';
        $request = new Request([], $params);
        $result = $this->controller->writeData($request, $context);

        static::assertSame(['validToken' => true], json_decode($result->getContent(), true));
        static::assertSame(Response::HTTP_OK, $result->getStatusCode());
    }

    public function testDownloadMedia(): void
    {
        $params = [
            'runUuid' => $this->runUuid,
            'limit' => 1000,
            'offset' => 0,
            'fileChunkByteSize' => 1000,
        ];

        $context = Context::createDefaultContext();
        $request = new Request([], $params);
        $result = $this->controller->processMedia($request, $context);

        static::assertSame(['validToken' => false], json_decode($result->getContent(), true));

        $params[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = 'testToken';

        $request = new Request([], $params);
        $result = $this->controller->processMedia($request, $context);
        $result = json_decode($result->getContent(), true);

        static::assertSame([
            'validToken' => true,
        ], $result);
    }

    public function testDownloadMediaWithInvalidRunUuid(): void
    {
        $inputWorkload = [
            [
                'uuid' => Uuid::randomHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::randomHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::randomHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],
        ];

        $properties = [
            'runUuid' => Uuid::randomHex(),
            'fileChunkByteSize' => 1000,
        ];

        $properties['workload'] = $inputWorkload;
        $request = new Request([], $properties);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->processMedia($request, $this->context);
    }

    public function testDownloadMediaWithInvalidConnection(): void
    {
        $inputWorkload = [
            [
                'uuid' => Uuid::randomHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::randomHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::randomHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],
        ];

        $properties = [
            'runUuid' => $this->invalidRunUuid,
            'fileChunkByteSize' => 1000,
        ];

        $properties['workload'] = $inputWorkload;
        $properties[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = 'testToken';
        $request = new Request([], $properties);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->processMedia($request, $this->context);
    }

    private function createDataRows(string $entityName, int $count = 1, bool $setWritten = false): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $this->dataRepo->create(
                [
                    [
                        'runId' => $this->runUuid,
                        'entity' => $entityName,
                        'raw' => [],
                        'converted' => [],
                        'written' => $setWritten,
                    ],
                ],
                $this->context
            );
        }
    }

    private function createMediaFileRows(int $count = 1, bool $setWritten = false, bool $setProcessed = false): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $this->mediaFileRepo->create(
                [
                    [
                        'runId' => $this->runUuid,
                        'uri' => 'foobar',
                        'fileName' => 'foobar',
                        'fileSize' => 100,
                        'mediaId' => Uuid::randomHex(),
                        'written' => $setWritten,
                        'processed' => $setProcessed,
                    ],
                ],
                $this->context
            );
        }
    }
}
