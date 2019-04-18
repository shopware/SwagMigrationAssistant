<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationNext\Controller\MigrationController;
use SwagMigrationNext\Exception\EntityNotExistsException;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Exception\MigrationRunUndefinedStatusException;
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationNext\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationNext\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\MediaFileProcessorRegistry;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\RunService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyHttpMediaDownloadService;
use SwagMigrationNext\Test\Mock\Migration\Service\DummyMediaFileProcessorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MigrationControllerTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

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

    protected function setUp(): void
    {
        $context = Context::createDefaultContext();
        $mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->profileUuidService = new MigrationProfileUuidService($this->profileRepo, Shopware55Profile::PROFILE_NAME, Shopware55LocalGateway::GATEWAY_NAME);
        $this->generalSettingRepo = $this->getContainer()->get('swag_migration_general_setting.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->dataSetRegistry = $this->getContainer()->get(DataSetRegistry::class);

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) {
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

        $mappingService = $this->getContainer()->get(MappingService::class);
        $accessTokenService = new SwagMigrationAccessTokenService(
            $this->runRepo
        );
        $dataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get(EntityWriter::class),
            $mappingService,
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get('swag_migration_logging.repository')
        );
        $this->controller = new MigrationController(
            $dataFetcher,
            $this->getContainer()->get(MigrationDataWriter::class),
            new DummyMediaFileProcessorService(
                $mediaFileRepo,
                new MediaFileProcessorRegistry(
                    [
                        new DummyHttpMediaDownloadService(),
                    ]
                )
            ),
            $accessTokenService,
            new RunService(
                $this->runRepo,
                $this->connectionRepo,
                $dataFetcher,
                $mappingService,
                $accessTokenService,
                new DataSelectionRegistry([]),
                $dataRepo,
                $mediaFileRepo
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

    public function testFetchMediaUuids(): void
    {
        $request = new Request([
            'runUuid' => $this->runUuid,
        ]);
        $context = Context::createDefaultContext();
        $result = $this->controller->fetchMediaUuids($request, $context);
        $mediaUuids = json_decode($result->getContent(), true);

        static::assertArrayHasKey('mediaUuids', $mediaUuids);
        static::assertCount(10, $mediaUuids['mediaUuids']);

        $this->expectException(MigrationWorkloadPropertyMissingException::class);
        $this->expectExceptionMessage('Required property "runUuid" for migration workload is missing');

        $request = new Request();
        $this->controller->fetchMediaUuids($request, $context);
    }

    public function testDownloadMedia(): void
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

        $params = [
            'runUuid' => $this->runUuid,
            'workload' => $inputWorkload,
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

        static::assertSame($result['workload'], $inputWorkload);

        $request = new Request([], [
            'runUuid' => $this->runUuid,
            SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME => 'testToken',
        ]);
        $result = $this->controller->processMedia($request, $context);
        $result = json_decode($result->getContent(), true);

        static::assertSame([
            'workload' => [],
            'validToken' => true,
        ], $result);
    }

    public function requiredDownloadMediaProperties(): array
    {
        return [
            ['runUuid'],
            ['uuid'],
            ['currentOffset'],
            ['state'],
        ];
    }

    /**
     * @dataProvider requiredDownloadMediaProperties
     */
    public function testDownloadMediaWithMissingProperty(string $missingProperty): void
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
            'runUuid' => $this->runUuid,
            'fileChunkByteSize' => 1000,
        ];

        $requestParamKeys = [
            'runUuid',
        ];

        if (!\in_array($missingProperty, $requestParamKeys, true)) {
            $this->expectException(MigrationWorkloadPropertyMissingException::class);

            foreach ($inputWorkload as &$workload) {
                unset($workload[$missingProperty]);
            }
            unset($workload);
        } else {
            $this->expectException(MigrationContextPropertyMissingException::class);

            unset($properties[$missingProperty]);
        }
        $properties['workload'] = $inputWorkload;

        $request = new Request([], $properties);
        $context = Context::createDefaultContext();
        try {
            $this->controller->processMedia($request, $context);
        } catch (\Exception $e) {
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            throw $e;
        }
    }
}
