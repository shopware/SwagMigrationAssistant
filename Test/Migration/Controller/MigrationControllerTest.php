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
use SwagMigrationNext\Exception\MigrationWorkloadPropertyMissingException;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Migration\Run\SwagMigrationAccessTokenService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyHttpAssetDownloadService;
use SwagMigrationNext\Test\Mock\Migration\Service\DummyProgressService;
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

    protected function setUp(): void
    {
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));
        $this->runUuid = Uuid::uuid4()->getHex();
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                    'accessToken' => 'testToken',
                ],
            ],
            Context::createDefaultContext()
        );

        $this->controller = new MigrationController(
            $this->getMigrationDataFetcher(
                $this->getContainer()->get('swag_migration_data.repository'),
                $this->getContainer()->get(Shopware55MappingService::class),
                $this->getContainer()->get(MediaFileService::class),
                $this->getContainer()->get('swag_migration_logging.repository')
            ),
            $this->getContainer()->get(MigrationDataWriter::class),
            new DummyHttpAssetDownloadService(),
            new DummyProgressService(),
            new SwagMigrationAccessTokenService($this->runRepo),
            $this->getContainer()->get('swag_migration_profile.repository'),
            $this->getContainer()->get('swag_migration_general_setting.repository')
        );
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

    public function testStartMigration(): void
    {
        $params = [
            'profileId' => $this->profileUuidService->getProfileUuid(),
        ];

        $context = Context::createDefaultContext();
        $customerId = Uuid::uuid4()->getHex();
        $context->getSourceContext()->setUserId($customerId);
        $request = new Request([], $params);
        $result = $this->controller->startMigration($request, $context);
        $resultArray = json_decode($result->getContent(), true);
        self::assertArrayHasKey('accessToken', $resultArray);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('accessToken', $resultArray['accessToken']));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();
        self::assertSame($run->getUserId(), mb_strtoupper($customerId));

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->expectExceptionMessage('Required property "profileId" for migration context is missing');
        $this->controller->startMigration(new Request(), $context);
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
        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->expectExceptionMessage('Required property "profileId" for migration context is missing');
        $this->controller->checkConnection($request, $context);
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
            'runId' => $this->runUuid,
            'workload' => $inputWorkload,
            'fileChunkByteSize' => 1000,
        ];

        $context = Context::createDefaultContext();
        $request = new Request([], $params);
        $result = $this->controller->downloadAssets($request, $context);

        static::assertSame(['validToken' => false], json_decode($result->getContent(), true));

        $params[SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME] = 'testToken';
        $request = new Request([], $params);
        $result = $this->controller->downloadAssets($request, $context);
        $result = json_decode($result->getContent(), true);

        self::assertSame($result['workload'], $inputWorkload);

        $request = new Request([], [
            'runId' => $this->runUuid,
            SwagMigrationAccessTokenService::ACCESS_TOKEN_NAME => 'testToken',
        ]);
        $result = $this->controller->downloadAssets($request, $context);
        $result = json_decode($result->getContent(), true);

        self::assertSame([
            'workload' => [],
        ], $result);
    }

    public function requiredDownloadAssetsProperties()
    {
        return [
            ['runId'],
            ['uuid'],
            ['currentOffset'],
            ['state'],
        ];
    }

    /**
     * @dataProvider requiredDownloadAssetsProperties
     */
    public function testDownloadAssetsWithMissingProperty(string $missingProperty): void
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
            'runId' => $this->runUuid,
            'fileChunkByteSize' => 1000,
        ];

        if ($missingProperty !== 'runId') {
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
        $this->controller->downloadAssets($request, $context);
    }

    public function testGetState(): void
    {
        $context = Context::createDefaultContext();
        $result = $this->controller->getState(new Request(), $context);
        $state = json_decode($result->getContent(), true);
        self::assertSame('SwagMigrationNext\Migration\Service\ProgressState', $state['_class']);
    }
}
