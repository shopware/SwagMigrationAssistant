<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Controller\MigrationController;
use SwagMigrationNext\Exception\MigrationContextPropertyMissingException;
use SwagMigrationNext\Exception\MigrationRunUndefinedStatusException;
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\MediaFileProcessorRegistry;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\PremappingReaderRegistry;
use SwagMigrationNext\Migration\Run\RunService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationProgressService;
use SwagMigrationNext\Migration\Service\PremappingService;
use SwagMigrationNext\Migration\Service\ProgressState;
use SwagMigrationNext\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyHttpMediaDownloadService;
use SwagMigrationNext\Test\Mock\Migration\Service\DummyMediaFileProcessorService;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        $context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) {
            $this->connectionId = Uuid::uuid4()->getHex();
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

        $this->runUuid = Uuid::uuid4()->getHex();
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
            $dataRepo,
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
            $this->getContainer()->get(MigrationProgressService::class),
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
            new PremappingService(new PremappingReaderRegistry([]), $mappingService),
            new DataSelectionRegistry([]),
            $this->connectionRepo,
            $this->runRepo
        );
    }

    public function testRunUndefinedStatus(): void
    {
        try {
            $run = new SwagMigrationRunEntity();
            $run->setStatus('invalidRunStatus');
        } catch (\Exception $e) {
            /* @var MigrationRunUndefinedStatusException $e */
            self::assertInstanceOf(MigrationRunUndefinedStatusException::class, $e);
            self::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            self::assertSame('Migration run status "invalidRunStatus" is not a valid status', $e->getMessage());
        }
    }

    public function testWriteDataWithInvalidRunId(): void
    {
        $context = Context::createDefaultContext();
        $params = [
            'runUuid' => Uuid::uuid4()->getHex(),
            'entity' => ProductDefinition::getEntityName(),
        ];

        $request = new Request([], $params);
        $result = $this->controller->writeData($request, $context);
        static::assertSame(['validToken' => true], json_decode($result->getContent(), true));
    }

    public function testTakeoverMigration(): void
    {
        $params = [
            'runUuid' => $this->runUuid,
        ];

        $context = Context::createDefaultContext();
        $customerId = Uuid::uuid4()->getHex();
        $context->getSourceContext()->setUserId($customerId);
        $request = new Request([], $params);
        $result = $this->controller->takeoverMigration($request, $context);
        $resultArray = json_decode($result->getContent(), true);
        self::assertArrayHasKey('accessToken', $resultArray);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('accessToken', $resultArray['accessToken']));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();
        self::assertSame($run->getUserId(), mb_strtoupper($customerId));

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->expectExceptionMessage('Required property "runUuid" for migration context is missing');
        $this->controller->takeoverMigration(new Request(), $context);
    }

    public function testGetProgressWithCreateMigration(): void
    {
        $context = Context::createDefaultContext();
        $customerId = Uuid::uuid4()->getHex();
        $context->getSourceContext()->setUserId($customerId);

        $params = [
            'connectionId' => $this->connectionId,
            'dataSelectionIds' => [
                'categories_products',
                'customers_orders',
                'media',
            ],
        ];
        $requestWithoutToken = new Request([], $params);
        $params[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = 'testToken';
        $requestWithToken = new Request([], $params);

        $abortedCriteria = new Criteria();
        $abortedCriteria->addFilter(new EqualsFilter('status', SwagMigrationRunEntity::STATUS_ABORTED));

        $runningCriteria = new Criteria();
        $runningCriteria->addFilter(new EqualsFilter('status', SwagMigrationRunEntity::STATUS_RUNNING));

        // Get state migration with invalid accessToken
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->getState($requestWithoutToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        self::assertSame(ProgressState::class, $state['_class']);
        self::assertFalse($state['migrationRunning']);
        self::assertFalse($state['validMigrationRunToken']);
        self::assertSame(ProgressState::STATUS_FETCH_DATA, $state['status']);
        self::assertSame(0, $totalAfter - $totalBefore);
        self::assertSame(0, $totalAbortedAfter - $totalAbortedBefore);
        self::assertSame(1, $totalProcessing);

        // Get state migration with valid accessToken and abort running migration
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->getState($requestWithToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        self::assertSame(ProgressState::class, $state['_class']);
        self::assertFalse($state['migrationRunning']);
        self::assertTrue($state['validMigrationRunToken']);
        self::assertSame(ProgressState::STATUS_FETCH_DATA, $state['status']);
        self::assertSame(0, $totalAfter - $totalBefore);
        self::assertSame(1, $totalAbortedAfter - $totalAbortedBefore);
        self::assertSame(0, $totalProcessing);

        // Create new migration without abort a running migration
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->createMigration($requestWithToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        self::assertSame(ProgressState::class, $state['_class']);
        self::assertFalse($state['migrationRunning']);
        self::assertTrue($state['validMigrationRunToken']);
        self::assertSame(1, $totalAfter - $totalBefore);
        self::assertSame(0, $totalAbortedAfter - $totalAbortedBefore);
        self::assertSame(1, $totalProcessing);

        // Call createMigration without accessToken and without abort running migration
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->createMigration($requestWithoutToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        self::assertSame(ProgressState::class, $state['_class']);
        self::assertFalse($state['migrationRunning']);
        self::assertFalse($state['validMigrationRunToken']);
        self::assertSame(0, $totalAfter - $totalBefore);
        self::assertSame(0, $totalAbortedAfter - $totalAbortedBefore);
        self::assertSame(1, $totalProcessing);

        // Get current accessToken and refresh token in request
        /** @var SwagMigrationRunEntity $currentRun */
        $currentRun = $this->runRepo->search($runningCriteria, $context)->first();
        $accessToken = $currentRun->getAccessToken();
        $params[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = $accessToken;
        $requestWithToken = new Request([], $params);

        // Call createMigration with accessToken and with abort running migration
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->createMigration($requestWithToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        self::assertSame(ProgressState::class, $state['_class']);
        self::assertFalse($state['migrationRunning']);
        self::assertTrue($state['validMigrationRunToken']);
        self::assertSame(0, $totalAfter - $totalBefore);
        self::assertSame(1, $totalAbortedAfter - $totalAbortedBefore);
        self::assertSame(0, $totalProcessing);
    }

    public function testCheckConnection(): void
    {
        $context = Context::createDefaultContext();

        $request = new Request([], [
            'connectionId' => $this->connectionId,
        ]);

        /**
         * @var JsonResponse
         */
        $result = $this->controller->checkConnection($request, $context);
        $environmentInformation = json_decode($result->getContent(), true);

        self::assertSame($environmentInformation['totals']['product'], 37);
        self::assertSame($environmentInformation['totals']['customer'], 2);
        self::assertSame($environmentInformation['totals']['category'], 8);
        self::assertSame($environmentInformation['totals']['media'], 23);
        self::assertSame($environmentInformation['totals']['order'], 0);
        self::assertSame($environmentInformation['totals']['translation'], 0);

        self::assertSame($environmentInformation['warningCode'], -1);
        self::assertSame($environmentInformation['warningMessage'], 'No warning.');

        self::assertSame($environmentInformation['errorCode'], -1);
        self::assertSame($environmentInformation['errorMessage'], 'No error.');

        $request = new Request();
        try {
            $this->controller->checkConnection($request, $context);
        } catch (\Exception $e) {
            /* @var MigrationContextPropertyMissingException $e */
            self::assertInstanceOf(MigrationContextPropertyMissingException::class, $e);
            self::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            self::assertSame('Required property "connectionId" for migration context is missing', $e->getMessage());
        }
    }

    public function testFetchData(): void
    {
        $context = Context::createDefaultContext();

        $params = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDefinition::getEntityName(),
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
            'entity' => ProductDefinition::getEntityName(),
        ];
        unset($properties[$missingProperty]);

        $request = new Request([], $properties);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->expectExceptionMessage(sprintf('Required property "%s" for migration context is missing', $missingProperty));
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
            'entity' => ProductDefinition::getEntityName(),
        ];
        unset($properties[$missingProperty]);

        $request = new Request([], $properties);
        $context = Context::createDefaultContext();

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->expectExceptionMessage(sprintf('Required property "%s" for migration context is missing', $missingProperty));

        $this->controller->writeData($request, $context);
    }

    public function testWriteData(): void
    {
        $params = [
            'runUuid' => $this->runUuid,
            'entity' => ProductDefinition::getEntityName(),
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

        self::assertArrayHasKey('mediaUuids', $mediaUuids);
        self::assertCount(10, $mediaUuids['mediaUuids']);

        $this->expectException(MigrationWorkloadPropertyMissingException::class);
        $this->expectExceptionMessage('Required property "runUuid" for migration workload is missing');

        $request = new Request();
        $this->controller->fetchMediaUuids($request, $context);
    }

    public function testDownloadMedia(): void
    {
        $inputWorkload = [
            [
                'uuid' => Uuid::uuid4()->getHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::uuid4()->getHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::uuid4()->getHex(),
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

        self::assertSame($result['workload'], $inputWorkload);

        $request = new Request([], [
            'runUuid' => $this->runUuid,
            SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME => 'testToken',
        ]);
        $result = $this->controller->processMedia($request, $context);
        $result = json_decode($result->getContent(), true);

        self::assertSame([
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
                'uuid' => Uuid::uuid4()->getHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::uuid4()->getHex(),
                'currentOffset' => 100,
                'state' => 'inProgress',
            ],

            [
                'uuid' => Uuid::uuid4()->getHex(),
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
            $this->expectExceptionMessage(sprintf('Required property "%s" for migration workload is missing', $missingProperty));

            foreach ($inputWorkload as &$dataset) {
                unset($dataset[$missingProperty]);
            }
            unset($dataset);
        } else {
            $this->expectException(MigrationContextPropertyMissingException::class);
            $this->expectExceptionMessage(sprintf('Required property "%s" for migration context is missing', $missingProperty));

            unset($properties[$missingProperty]);
        }
        $properties['workload'] = $inputWorkload;

        $request = new Request([], $properties);
        $context = Context::createDefaultContext();
        try {
            $this->controller->processMedia($request, $context);
        } catch (\Exception $e) {
            self::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            throw $e;
        }
    }

    public function testGetState(): void
    {
        $context = Context::createDefaultContext();
        $result = $this->controller->getState(new Request(), $context);
        $state = json_decode($result->getContent(), true);
        self::assertSame(ProgressState::class, $state['_class']);
    }
}
