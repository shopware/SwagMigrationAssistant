<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Controller;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
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
use SwagMigrationAssistant\Controller\StatusController;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Exception\MigrationIsRunningException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationProgressService;
use SwagMigrationAssistant\Migration\Service\ProgressState;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StatusControllerTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;

    /**
     * @var StatusController
     */
    private $controller;

    /**
     * @var string
     */
    private $runUuid;

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
    private $connectionRepo;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $invalidRunUuid;

    /**
     * @var string
     */
    private $invalidConnectionId;

    /**
     * @var MigrationContextFactoryInterface
     */
    private $migrationContextFactory;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $dataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->generalSettingRepo = $this->getContainer()->get('swag_migration_general_setting.repository');
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $themeRepo = $this->getContainer()->get('theme.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->migrationContextFactory = $this->getContainer()->get(MigrationContextFactory::class);
        $loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context): void {
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
                        'profileName' => Shopware55Profile::PROFILE_NAME,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $context
            );

            $this->invalidConnectionId = Uuid::randomHex();
            $this->connectionRepo->create(
                [
                    [
                        'id' => $this->invalidConnectionId,
                        'name' => 'myInvalidConnection',
                        'profileName' => Shopware55Profile::PROFILE_NAME,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
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
            $this->getContainer()->get('swag_migration_logging.repository'),
            $this->getContainer()->get(SwagMigrationDataDefinition::class),
            $this->getContainer()->get(DataSetRegistry::class),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get(ReaderRegistry::class)
        );
        $this->controller = new StatusController(
            $dataFetcher,
            $this->getContainer()->get(MigrationProgressService::class),
            new RunService(
                $this->runRepo,
                $this->connectionRepo,
                $dataFetcher,
                $accessTokenService,
                new DataSelectionRegistry([
                    new ProductDataSelection(),
                    new CustomerAndOrderDataSelection(),
                ]),
                $dataRepo,
                $mediaFileRepo,
                $salesChannelRepo,
                $themeRepo,
                $this->getContainer()->get(EntityIndexerRegistry::class),
                $this->getContainer()->get(ThemeService::class),
                $mappingService,
                $this->getContainer()->get('cache.object'),
                $this->getContainer()->get(SwagMigrationDataDefinition::class),
                $this->getContainer()->get(Connection::class),
                new LoggingService($loggingRepo),
                $this->getContainer()->get(StoreService::class),
                $this->getContainer()->get('messenger.bus.shopware')
            ),
            new DataSelectionRegistry([
                new ProductDataSelection(),
                new CustomerAndOrderDataSelection(),
            ]),
            $this->connectionRepo,
            $this->getContainer()->get(ProfileRegistry::class),
            $this->getContainer()->get(GatewayRegistry::class),
            $this->migrationContextFactory,
            $this->generalSettingRepo
        );
    }

    public function connectionProvider()
    {
        return [
            [
                Uuid::randomHex(),
                Shopware54Profile::PROFILE_NAME,
                'myConnection54',
            ],
            [
                Uuid::randomHex(),
                Shopware55Profile::PROFILE_NAME,
                'myConnection55',
            ],
            [
                Uuid::randomHex(),
                Shopware56Profile::PROFILE_NAME,
                'myConnection56',
            ],
        ];
    }

    public function testGetProfileInformation(): void
    {
        $request = new Request(['profileName' => 'shopware55', 'gatewayName' => 'local'], []);
        $response = $this->controller->getProfileInformation($request);

        static::assertInstanceOf(JsonResponse::class, $response);
        $this->assertJSON($response->getContent());

        $info = json_decode($response->getContent(), true);
        static::assertArrayHasKey('profile', $info);
        static::assertArrayHasKey('name', $info['profile']);
        static::assertArrayHasKey('sourceSystemName', $info['profile']);
        static::assertArrayHasKey('version', $info['profile']);
        static::assertArrayHasKey('author', $info['profile']);
        static::assertArrayHasKey('icon', $info['profile']);
        static::assertArrayHasKey('gateway', $info);
        static::assertArrayHasKey('name', $info['gateway']);
        static::assertArrayHasKey('snippet', $info['gateway']);
    }

    public function testGetProfiles(): void
    {
        $response = $this->controller->getProfiles();

        static::assertInstanceOf(JsonResponse::class, $response);
        $this->assertJSON($response->getContent());
    }

    public function testGetGatewaysWithoutProfileName(): void
    {
        $request = new Request();
        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->getGateways($request);
    }

    public function testGetGateways(): void
    {
        $request = new Request(['profileName' => 'shopware55'], []);
        $response = $this->controller->getGateways($request);

        static::assertInstanceOf(JsonResponse::class, $response);
        $this->assertJSON($response->getContent());

        $gateways = json_decode($response->getContent(), true);
        static::assertSame('local', $gateways[0]['name']);
        static::assertNotNull($gateways[0]['snippet']);
        static::assertSame('api', $gateways[1]['name']);
        static::assertNotNull($gateways[1]['snippet']);
    }

    public function testUpdateConnectionCredentials(): void
    {
        $context = Context::createDefaultContext();
        $params = [
            'connectionId' => $this->connectionId,
            'credentialFields' => [
                'testCredentialField1' => 'field1',
                'testCredentialField2' => 'field2',
            ],
        ];

        $this->runRepo->update([
            [
                'id' => $this->runUuid,
                'status' => SwagMigrationRunEntity::STATUS_ABORTED,
            ],
        ], $context);

        $request = new Request([], $params);
        $this->controller->updateConnectionCredentials($request, $context);

        /** @var SwagMigrationConnectionEntity $connection */
        $connection = $this->connectionRepo->search(new Criteria([$this->connectionId]), $context)->first();
        static::assertSame($connection->getCredentialFields(), $params['credentialFields']);
    }

    public function testUpdateConnectionCredentialsWithoutConnectionId(): void
    {
        $params = [
            'credentialFields' => [
                'testCredentialField1' => 'field1',
                'testCredentialField2' => 'field2',
            ],
        ];
        $request = new Request([], $params);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->updateConnectionCredentials($request, $this->context);
    }

    public function testUpdateConnectionCredentialsWithInvalidConnectionId(): void
    {
        $params = [
            'connectionId' => Uuid::randomHex(),
            'credentialFields' => [
                'testCredentialField1' => 'field1',
                'testCredentialField2' => 'field2',
            ],
        ];
        $request = new Request([], $params);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->updateConnectionCredentials($request, $this->context);
    }

    public function testUpdateConnectionCredentialsWithRunningMigration(): void
    {
        $this->expectException(MigrationIsRunningException::class);
        $context = Context::createDefaultContext();
        $params = [
            'connectionId' => $this->connectionId,
            'credentialFields' => [
                'testCredentialField1' => 'field1',
                'testCredentialField2' => 'field2',
            ],
        ];

        $request = new Request([], $params);
        $this->controller->updateConnectionCredentials($request, $context);
    }

    /**
     * @dataProvider connectionProvider
     */
    public function testGetDataSelection(string $connectionId, string $profileName, string $connectionName): void
    {
        $this->createConnection($connectionId, $profileName, $connectionName);

        $context = Context::createDefaultContext();
        $request = new Request(['connectionId' => $connectionId]);
        $result = $this->controller->getDataSelection($request, $context);
        $state = json_decode($result->getContent(), true);

        static::assertSame($state[0]['id'], 'products');
        static::assertSame($state[0]['entityNames'][0], DefaultEntities::MEDIA_FOLDER);
        static::assertSame($state[0]['entityNames'][1], DefaultEntities::PRODUCT_CUSTOM_FIELD);
        static::assertSame($state[0]['entityNames'][2], DefaultEntities::PRODUCT_PRICE_CUSTOM_FIELD);
        static::assertSame($state[0]['entityNames'][3], DefaultEntities::PRODUCT_MANUFACTURER_CUSTOM_FIELD);
        static::assertSame($state[0]['entityNames'][4], DefaultEntities::PRODUCT);
        static::assertSame($state[0]['entityNames'][5], DefaultEntities::PROPERTY_GROUP_OPTION);
        static::assertSame($state[0]['entityNames'][6], DefaultEntities::PRODUCT_OPTION_RELATION);
        static::assertSame($state[0]['entityNames'][7], DefaultEntities::PRODUCT_PROPERTY_RELATION);
        static::assertSame($state[0]['entityNames'][8], DefaultEntities::TRANSLATION);

        static::assertSame($state[1]['id'], 'customersOrders');
        static::assertSame($state[1]['entityNames'][0], DefaultEntities::CUSTOMER_CUSTOM_FIELD);
        static::assertSame($state[1]['entityNames'][1], DefaultEntities::CUSTOMER);
        static::assertSame($state[1]['entityNames'][2], DefaultEntities::SHIPPING_METHOD);
        static::assertSame($state[1]['entityNames'][3], DefaultEntities::ORDER_CUSTOM_FIELD);
        static::assertSame($state[1]['entityNames'][4], DefaultEntities::ORDER);
    }

    public function testGetDataSelectionWithoutConnectionId(): void
    {
        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->getDataSelection(new Request(), $this->context);
    }

    public function testGetDataSelectionWithInvalidConnectionId(): void
    {
        $request = new Request(['connectionId' => Uuid::randomHex()]);
        $this->expectException(EntityNotExistsException::class);
        $this->controller->getDataSelection($request, $this->context);
    }

    public function testGetState(): void
    {
        $context = Context::createDefaultContext();
        $result = $this->controller->getState(new Request(), $context);
        $state = json_decode($result->getContent(), true);
        static::assertTrue($this->isJsonArrayTypeOfProgressState($state));
    }

    public function testGetStateWithCreateMigration(): void
    {
        $userId = Uuid::randomHex();
        $origin = new AdminApiSource($userId);
        $origin->setIsAdmin(true);
        $context = Context::createDefaultContext($origin);

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
        static::assertTrue($this->isJsonArrayTypeOfProgressState($state));
        static::assertFalse($state['migrationRunning']);
        static::assertFalse($state['validMigrationRunToken']);
        static::assertSame(ProgressState::STATUS_FETCH_DATA, $state['status']);
        static::assertSame(0, $totalAfter - $totalBefore);
        static::assertSame(0, $totalAbortedAfter - $totalAbortedBefore);
        static::assertSame(1, $totalProcessing);

        // Get state migration with valid accessToken and abort running migration
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->getState($requestWithToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        static::assertTrue($this->isJsonArrayTypeOfProgressState($state));
        static::assertFalse($state['migrationRunning']);
        static::assertTrue($state['validMigrationRunToken']);
        static::assertSame(ProgressState::STATUS_FETCH_DATA, $state['status']);
        static::assertSame(0, $totalAfter - $totalBefore);
        static::assertSame(1, $totalAbortedAfter - $totalAbortedBefore);
        static::assertSame(0, $totalProcessing);

        // Create new migration without abort a running migration
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->createMigration($requestWithToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        static::assertTrue($this->isJsonArrayTypeOfProgressState($state));
        static::assertFalse($state['migrationRunning']);
        static::assertTrue($state['validMigrationRunToken']);
        static::assertSame(1, $totalAfter - $totalBefore);
        static::assertSame(0, $totalAbortedAfter - $totalAbortedBefore);
        static::assertSame(1, $totalProcessing);

        // Call createMigration without accessToken and without abort running migration
        $totalAbortedBefore = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalBefore = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $result = $this->controller->createMigration($requestWithoutToken, $context);
        $state = json_decode($result->getContent(), true);
        $totalAfter = $this->runRepo->search(new Criteria(), $context)->getTotal();
        $totalAbortedAfter = $this->runRepo->search($abortedCriteria, $context)->getTotal();
        $totalProcessing = $this->runRepo->search($runningCriteria, $context)->getTotal();
        static::assertTrue($this->isJsonArrayTypeOfProgressState($state));
        static::assertFalse($state['migrationRunning']);
        static::assertFalse($state['validMigrationRunToken']);
        static::assertSame(0, $totalAfter - $totalBefore);
        static::assertSame(0, $totalAbortedAfter - $totalAbortedBefore);
        static::assertSame(1, $totalProcessing);

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
        static::assertTrue($this->isJsonArrayTypeOfProgressState($state));
        static::assertFalse($state['migrationRunning']);
        static::assertTrue($state['validMigrationRunToken']);
        static::assertSame(0, $totalAfter - $totalBefore);
        static::assertSame(1, $totalAbortedAfter - $totalAbortedBefore);
        static::assertSame(0, $totalProcessing);
    }

    public function testCreateMigrationWithoutConnectionId(): void
    {
        $params = [
            'connectionId' => $this->connectionId,
        ];
        $requestWithToken = new Request([], $params);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->createMigration($requestWithToken, $this->context);
    }

    public function testCreateMigrationWithoutDataSelectionIds(): void
    {
        $params = [
            'dataSelectionIds' => [
                'categories_products',
                'customers_orders',
                'media',
            ],
        ];
        $requestWithToken = new Request([], $params);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->createMigration($requestWithToken, $this->context);
    }

    public function testTakeoverMigration(): void
    {
        $params = [
            'runUuid' => $this->runUuid,
        ];

        $userId = Uuid::randomHex();
        $origin = new AdminApiSource($userId);
        $origin->setIsAdmin(true);
        $context = Context::createDefaultContext($origin);

        $request = new Request([], $params);
        $result = $this->controller->takeoverMigration($request, $context);
        $resultArray = json_decode($result->getContent(), true);
        static::assertArrayHasKey('accessToken', $resultArray);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('accessToken', $resultArray['accessToken']));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search($criteria, $context)->first();
        static::assertSame($run->getUserId(), mb_strtoupper($userId));

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->takeoverMigration(new Request(), $context);
    }

    public function testCheckConnection(): void
    {
        $context = Context::createDefaultContext();

        $request = new Request([], [
            'connectionId' => $this->connectionId,
        ]);

        $result = $this->controller->checkConnection($request, $context);
        $environmentInformation = json_decode($result->getContent(), true);

        static::assertSame($environmentInformation['totals']['product']['total'], 37);
        static::assertSame($environmentInformation['totals']['customer']['total'], 2);
        static::assertSame($environmentInformation['totals']['category']['total'], 8);
        static::assertSame($environmentInformation['totals']['media']['total'], 23);
        static::assertSame($environmentInformation['totals']['order']['total'], 0);
        static::assertSame($environmentInformation['totals']['translation']['total'], 0);

        static::assertSame($environmentInformation['requestStatus']['code'], '');
        static::assertSame($environmentInformation['requestStatus']['message'], 'No error.');

        $request = new Request();
        try {
            $this->controller->checkConnection($request, $context);
        } catch (\Exception $e) {
            /* @var MigrationContextPropertyMissingException $e */
            static::assertInstanceOf(MigrationContextPropertyMissingException::class, $e);
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            static::assertArrayHasKey('property', $e->getParameters());
            static::assertSame($e->getParameters()['property'], 'connectionId');
        }
    }

    public function testCheckConnectionWithInvalidConnectionId(): void
    {
        $request = new Request([], ['connectionId' => Uuid::randomHex()]);
        $this->expectException(EntityNotExistsException::class);
        $this->controller->checkConnection($request, $this->context);
    }

    public function testCheckConnectionWithoutCredentials(): void
    {
        $request = new Request([], ['connectionId' => $this->invalidConnectionId]);
        $response = $this->controller->checkConnection($request, $this->context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $jsonResponse = json_decode($response->getContent(), true);
        //this is not the actual expected response because of the DummyMigrationDataFetcher
        static::assertSame('Shopware', $jsonResponse['sourceSystemName']);
        static::assertSame('', $jsonResponse['requestStatus']['code']);
        static::assertSame('No error.', $jsonResponse['requestStatus']['message']);
    }

    public function testAbortMigrationWithoutRunUuid(): void
    {
        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->abortMigration(new Request(), $this->context);
    }

    public function testAbortMigration(): void
    {
        $request = new Request([], ['runUuid' => $this->runUuid]);
        $this->controller->abortMigration($request, $this->context);

        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search(new Criteria([$this->runUuid]), $this->context)->first();
        static::assertSame('aborted', $run->getStatus());
    }

    public function testFinishMigrationWithoutRunUuid(): void
    {
        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->finishMigration(new Request(), $this->context);
    }

    public function testFinishMigration(): void
    {
        $request = new Request([], ['runUuid' => $this->runUuid]);
        $this->controller->finishMigration($request, $this->context);

        /** @var SwagMigrationRunEntity $run */
        $run = $this->runRepo->search(new Criteria([$this->runUuid]), $this->context)->first();
        static::assertSame('finished', $run->getStatus());
    }

    public function testGetResetStatus(): void
    {
        $id = $this->generalSettingRepo->searchIds(new Criteria(), $this->context)->firstId();
        $this->generalSettingRepo->update([['id' => $id, 'isReset' => false]], $this->context);

        $result = $this->controller->getResetStatus($this->context)->getContent();
        static::assertSame('false', $result);

        $this->generalSettingRepo->update([['id' => $id, 'isReset' => true]], $this->context);

        $result = $this->controller->getResetStatus($this->context)->getContent();
        static::assertSame('true', $result);
    }

    private function isJsonArrayTypeOfProgressState(array $state): bool
    {
        return array_key_exists('migrationRunning', $state)
            && array_key_exists('runId', $state)
            && array_key_exists('runProgress', $state)
        ;
    }

    private function createConnection(string $connectionId, string $profileName, string $connectionName): void
    {
        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionId, $profileName, $connectionName): void {
            $this->connectionRepo->create(
                [
                    [
                        'id' => $connectionId,
                        'name' => $connectionName,
                        'credentialFields' => [
                            'endpoint' => 'testEndpoint',
                            'apiUser' => 'testUser',
                            'apiKey' => 'testKey',
                        ],
                        'profileName' => $profileName,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $context
            );
        });
    }
}
