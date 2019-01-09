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
use SwagMigrationNext\Migration\Asset\MediaFileProcessorRegistry;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Migration\Run\SwagMigrationAccessTokenService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationProgressService;
use SwagMigrationNext\Migration\Service\ProgressState;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyHttpAssetDownloadService;
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

    protected function setUp(): void
    {
        $this->profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->profileUuidService = new MigrationProfileUuidService($this->profileRepo);
        $this->generalSettingRepo = $this->getContainer()->get('swag_migration_general_setting.repository');
        $this->runUuid = Uuid::uuid4()->getHex();
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'credentialFields' => [
                        'endpoint' => 'testEndpoint',
                        'apiUser' => 'testUser',
                        'apiKey' => 'testKey',
                    ],
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                    'accessToken' => 'testToken',
                ],
            ],
            Context::createDefaultContext()
        );

        $dataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get('swag_migration_data.repository'),
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get('swag_migration_logging.repository')
        );
        $this->controller = new MigrationController(
            $dataFetcher,
            $this->getContainer()->get(MigrationDataWriter::class),
            new DummyMediaFileProcessorService(
                $this->getContainer()->get('swag_migration_media_file.repository'),
                new MediaFileProcessorRegistry(
                    [
                        new DummyHttpAssetDownloadService(),
                    ]
                )
            ),
            $this->getContainer()->get(MigrationProgressService::class),
            new SwagMigrationAccessTokenService(
                $this->runRepo,
                $this->profileRepo,
                $dataFetcher
            ),
            $this->profileRepo
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

        $credentialFields = [
                'endpoint' => 'testEndpoint',
                'apiUser' => 'testUser',
                'apiKey' => 'testKey',
        ];

        $this->profileRepo->update(
            [
                [
                    'id' => $this->profileUuidService->getProfileUuid(),
                    'credentialFields' => $credentialFields,
                ],
            ],
            $context
        );

        $params = [
            'profileId' => $this->profileUuidService->getProfileUuid(),
            'credentialFields' => $credentialFields,
            'totals' => [
                'toBeFetched' => [
                    'product' => 5,
                ],
            ],
            'additionalData' => require __DIR__ . '/../../_fixtures/run_additional_data.php',
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
        self::assertSame('SwagMigrationNext\Migration\Service\ProgressState', $state['_class']);
        self::assertTrue($state['migrationRunning']);
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
        self::assertSame('SwagMigrationNext\Migration\Service\ProgressState', $state['_class']);
        self::assertTrue($state['migrationRunning']);
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
        self::assertSame('SwagMigrationNext\Migration\Service\ProgressState', $state['_class']);
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
        self::assertSame('SwagMigrationNext\Migration\Service\ProgressState', $state['_class']);
        self::assertTrue($state['migrationRunning']);
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
        self::assertSame('SwagMigrationNext\Migration\Service\ProgressState', $state['_class']);
        self::assertTrue($state['migrationRunning']);
        self::assertTrue($state['validMigrationRunToken']);
        self::assertSame(0, $totalAfter - $totalBefore);
        self::assertSame(1, $totalAbortedAfter - $totalAbortedBefore);
        self::assertSame(0, $totalProcessing);
    }

    public function testCheckConnection(): void
    {
        $context = Context::createDefaultContext();

        /** @var $profileRepo EntityRepositoryInterface */
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profile', 'shopware55'));
        $criteria->addFilter(new EqualsFilter('gateway', 'local'));
        $profileResult = $profileRepo->search($criteria, $context);
        /** @var $profile SwagMigrationProfileEntity */
        $profile = $profileResult->first();

        $request = new Request([], [
            'profileId' => $profile->getId(),
        ]);

        /**
         * @var JsonResponse
         */
        $result = $this->controller->checkConnection($request, $context);
        $environmentInformation = json_decode($result->getContent(), true);

        self::assertSame($environmentInformation['productTotal'], 37);
        self::assertSame($environmentInformation['customerTotal'], 2);
        self::assertSame($environmentInformation['categoryTotal'], 8);
        self::assertSame($environmentInformation['assetTotal'], 23);
        self::assertSame($environmentInformation['orderTotal'], 0);
        self::assertSame($environmentInformation['translationTotal'], 0);

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
            self::assertSame('Required property "profileId" for migration context is missing', $e->getMessage());
        }
    }

    public function testFetchData(): void
    {
        $context = Context::createDefaultContext();

        /** @var $profileRepo EntityRepositoryInterface */
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profile', 'shopware55'));
        $criteria->addFilter(new EqualsFilter('gateway', 'api'));
        $profileResult = $profileRepo->search($criteria, $context);
        /** @var $profile SwagMigrationProfileEntity */
        $profile = $profileResult->first();

        $params = [
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'profileId' => $profile->getId(),
            'runUuid' => $this->runUuid,
            'gateway' => 'local',
            'entity' => ProductDefinition::getEntityName(),
            'credentialFields' => [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
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

    public function requiredFetchDataProperties()
    {
        return [
            ['runUuid'],
            ['profileId'],
            ['profileName'],
            ['gateway'],
            ['entity'],
            ['credentialFields'],
        ];
    }

    /**
     * @dataProvider requiredFetchDataProperties
     */
    public function testFetchDataWithMissingProperty(string $missingProperty): void
    {
        $context = Context::createDefaultContext();

        /** @var $profileRepo EntityRepositoryInterface */
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profile', 'shopware55'));
        $criteria->addFilter(new EqualsFilter('gateway', 'api'));
        $profileResult = $profileRepo->search($criteria, $context);
        /** @var $profile SwagMigrationProfileEntity */
        $profile = $profileResult->first();
        $properties = [
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'profileId' => $profile->getId(),
            'runUuid' => $this->runUuid,
            'gateway' => 'local',
            'entity' => ProductDefinition::getEntityName(),
            'credentialFields' => [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
        ];
        unset($properties[$missingProperty]);

        $request = new Request([], $properties);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->expectExceptionMessage(sprintf('Required property "%s" for migration context is missing', $missingProperty));
        $this->controller->fetchData($request, $context);
    }

    public function requiredWriteDataProperties()
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
            'runId' => $this->runUuid,
        ]);
        $context = Context::createDefaultContext();
        $result = $this->controller->fetchMediaUuids($request, $context);
        $mediaUuids = json_decode($result->getContent(), true);

        self::assertArrayHasKey('mediaUuids', $mediaUuids);
        self::assertCount(10, $mediaUuids['mediaUuids']);

        $this->expectException(MigrationWorkloadPropertyMissingException::class);
        $this->expectExceptionMessage('Required property "runId" for migration workload is missing');

        $request = new Request();
        $this->controller->fetchMediaUuids($request, $context);
    }

    public function testDownloadAssets(): void
    {
        $context = Context::createDefaultContext();
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

        /** @var $profileRepo RepositoryInterface */
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profile', 'shopware55'));
        $criteria->addFilter(new EqualsFilter('gateway', 'api'));
        $profileResult = $profileRepo->search($criteria, $context);
        /** @var $profile SwagMigrationProfileEntity */
        $profile = $profileResult->first();

        $params = [
            'runId' => $this->runUuid,
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'profileId' => $profile->getId(),
            'gateway' => 'local',
            'workload' => $inputWorkload,
            'fileChunkByteSize' => 1000,
        ];

        $context = Context::createDefaultContext();
        $request = new Request([], $params);
        $result = $this->controller->processAssets($request, $context);

        static::assertSame(['validToken' => false], json_decode($result->getContent(), true));

        $params[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = 'testToken';
        $request = new Request([], $params);
        $result = $this->controller->processAssets($request, $context);
        $result = json_decode($result->getContent(), true);

        self::assertSame($result['workload'], $inputWorkload);

        $request = new Request([], [
            'runId' => $this->runUuid,
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'profileId' => $profile->getId(),
            'gateway' => 'local',
            SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME => 'testToken',
        ]);
        $result = $this->controller->processAssets($request, $context);
        $result = json_decode($result->getContent(), true);

        self::assertSame([
            'workload' => [],
            'validToken' => true,
        ], $result);
    }

    public function requiredDownloadAssetsProperties()
    {
        return [
            ['runId'],
            ['uuid'],
            ['currentOffset'],
            ['state'],
            ['profileName'],
            ['profileId'],
            ['gateway'],
        ];
    }

    /**
     * @dataProvider requiredDownloadAssetsProperties
     */
    public function testDownloadAssetsWithMissingProperty(string $missingProperty): void
    {
        $context = Context::createDefaultContext();
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

        /** @var $profileRepo RepositoryInterface */
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profile', 'shopware55'));
        $criteria->addFilter(new EqualsFilter('gateway', 'api'));
        $profileResult = $profileRepo->search($criteria, $context);
        /** @var $profile SwagMigrationProfileStruct */
        $profile = $profileResult->first();

        $properties = [
            'runId' => $this->runUuid,
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'profileId' => $profile->getId(),
            'gateway' => 'local',
            'fileChunkByteSize' => 1000,
        ];

        $requestParamKeys = [
            'runId',
            'profileName',
            'profileId',
            'gateway',
        ];

        if (!\in_array($missingProperty, $requestParamKeys, true)) {
            $this->expectException(MigrationWorkloadPropertyMissingException::class);
            $this->expectExceptionMessage(sprintf('Required property "%s" for migration workload is missing', $missingProperty));

            foreach ($inputWorkload as &$dataset) {
                unset($dataset[$missingProperty]);
            }
        } else {
            $this->expectException(MigrationContextPropertyMissingException::class);
            $this->expectExceptionMessage(sprintf('Required property "%s" for migration context is missing', $missingProperty));

            unset($properties[$missingProperty]);
        }
        $properties['workload'] = $inputWorkload;

        $request = new Request([], $properties);
        $context = Context::createDefaultContext();
        try {
            $this->controller->processAssets($request, $context);
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
        self::assertSame('SwagMigrationNext\Migration\Service\ProgressState', $state['_class']);
    }
}
