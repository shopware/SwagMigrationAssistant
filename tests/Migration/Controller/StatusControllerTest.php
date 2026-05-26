<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Controller;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Theme\ThemeService;
use SwagMigrationAssistant\Controller\StatusController;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Connection\Fingerprint\MigrationFingerprintService;
use SwagMigrationAssistant\Migration\Connection\MigrationConnectionFactory;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\History\LogGroupingService;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\RunTransitionService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\PremappingService;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ManufacturerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductOptionRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductPropertyRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGatewayFail;
use SwagMigrationAssistant\Test\Profile\Shopware\Gateway\Local\LocalCredentialTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Package('fundamentals@after-sales')]
class StatusControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use LocalCredentialTrait;
    use MigrationServicesTrait;

    private StatusController $controller;

    private string $runUuid;

    /**
     * @var EntityRepository<SwagMigrationRunCollection>
     */
    private EntityRepository $runRepo;

    /**
     * @var EntityRepository<GeneralSettingCollection>
     */
    private EntityRepository $generalSettingRepo;

    /**
     * @var EntityRepository<SwagMigrationConnectionCollection>
     */
    private EntityRepository $connectionRepo;

    private string $connectionId = '';

    private Context $context;

    private string $invalidConnectionId = '';

    protected function setUp(): void
    {
        $this->connectionSetup();

        $this->context = Context::createDefaultContext();
        $this->connectionRepo = static::getContainer()->get('swag_migration_connection.repository');
        $this->generalSettingRepo = static::getContainer()->get('swag_migration_general_setting.repository');
        $salesChannelRepo = static::getContainer()->get('sales_channel.repository');
        $themeRepo = static::getContainer()->get('theme.repository');
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $migrationContextFactory = static::getContainer()->get(MigrationContextFactory::class);
        $loggingRepo = static::getContainer()->get('swag_migration_logging.repository');

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context): void {
            $this->connectionId = Uuid::randomHex();
            $this->connectionRepo->create(
                [
                    [
                        'id' => $this->connectionId,
                        'name' => 'myConnection',
                        'credentialFields' => $this->connection->getCredentialFields(),
                        'profileName' => $this->connection->getProfileName(),
                        'gatewayName' => $this->connection->getGatewayName(),
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
                    'step' => MigrationStep::FETCHING->value,
                ],
            ],
            Context::createDefaultContext()
        );

        $dataFetcher = $this->getMigrationDataFetcher(
            static::getContainer()->get('swag_migration_logging.repository'),
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository'),
            static::getContainer()->get(ReaderRegistry::class)
        );
        $this->controller = $this->createStatusController($dataFetcher);
    }

    public function tesIsTruncatingMigrationData(): void
    {
        $id = $this->generalSettingRepo->searchIds(new Criteria(), $this->context)->firstId();
        $this->generalSettingRepo->update([['id' => $id, 'isReset' => false]], $this->context);

        $result = $this->controller->isTruncatingMigrationData($this->context)->getContent();
        static::assertSame('false', $result);

        $this->generalSettingRepo->update([['id' => $id, 'isReset' => true]], $this->context);

        $result = $this->controller->isTruncatingMigrationData($this->context)->getContent();
        static::assertSame('true', $result);
    }

    public function testIsResettingChecksumsWhenTrue(): void
    {
        $id = $this->generalSettingRepo->searchIds(new Criteria(), $this->context)->firstId();
        $this->generalSettingRepo->update([['id' => $id, 'isResettingChecksums' => true]], $this->context);

        $result = $this->controller->isResettingChecksums($this->context)->getContent();
        static::assertSame('true', $result);
    }

    public function testIsResettingChecksumsWhenFalse(): void
    {
        $id = $this->generalSettingRepo->searchIds(new Criteria(), $this->context)->firstId();
        $this->generalSettingRepo->update([['id' => $id, 'isResettingChecksums' => false]], $this->context);

        $result = $this->controller->isResettingChecksums($this->context)->getContent();
        static::assertSame('false', $result);
    }

    public function testIsResettingChecksumsWithoutSettings(): void
    {
        $id = $this->generalSettingRepo->searchIds(new Criteria(), $this->context)->firstId();

        if ($id) {
            $this->generalSettingRepo->delete([['id' => $id]], $this->context);
        }

        $result = $this->controller->isResettingChecksums($this->context);
        static::assertSame('false', $result->getContent());
    }

    /**
     * @return list<list<string>>
     */
    public static function connectionProvider(): array
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
        $info = $this->jsonResponseToArray($response);

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
        $this->jsonResponseToArray($response);
    }

    public function testGetGatewaysWithoutProfileName(): void
    {
        try {
            $this->controller->getGateways(new Request());
        } catch (RoutingException $e) {
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            static::assertSame(RoutingException::MISSING_REQUEST_PARAMETER_CODE, $e->getErrorCode());
            static::assertArrayHasKey('parameterName', $e->getParameters());
            static::assertSame($e->getParameters()['parameterName'], 'profileName');
        }
    }

    public function testGetGateways(): void
    {
        $request = new Request(['profileName' => 'shopware55'], []);
        $response = $this->controller->getGateways($request);
        $gateways = $this->jsonResponseToArray($response);

        static::assertSame('local', $gateways[0]['name']);
        static::assertNotNull($gateways[0]['snippet']);
        static::assertSame('api', $gateways[1]['name']);
        static::assertNotNull($gateways[1]['snippet']);
    }

    public function testUpdateConnectionCredentials(): void
    {
        $params = [
            'connectionId' => $this->connectionId,
            'credentialFields' => [
                'dbHost' => 'localhost',
                'dbPort' => '3306',
                'dbName' => 'shopware',
            ],
        ];

        $this->runRepo->update([
            [
                'id' => $this->runUuid,
                'step' => MigrationStep::ABORTED->value,
            ],
        ], $this->context);

        $request = new Request([], $params);
        $this->controller->updateConnectionCredentials($request, $this->context);

        $connection = $this->connectionRepo->search(new Criteria([$this->connectionId]), $this->context)->getEntities()->first();
        static::assertNotNull($connection);
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

        try {
            $this->controller->updateConnectionCredentials($request, $this->context);
        } catch (RoutingException $e) {
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            static::assertSame(RoutingException::MISSING_REQUEST_PARAMETER_CODE, $e->getErrorCode());
            static::assertArrayHasKey('parameterName', $e->getParameters());
            static::assertSame($e->getParameters()['parameterName'], 'connectionId');
        }
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

        try {
            $this->controller->updateConnectionCredentials($request, $this->context);
        } catch (MigrationException $e) {
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertSame(MigrationException::NO_CONNECTION_FOUND, $e->getErrorCode());
        }
    }

    public function testUpdateConnectionCredentialsWithRunningMigration(): void
    {
        $params = [
            'connectionId' => $this->connectionId,
            'credentialFields' => [
                'testCredentialField1' => 'field1',
                'testCredentialField2' => 'field2',
            ],
        ];

        $request = new Request([], $params);

        try {
            $this->controller->updateConnectionCredentials($request, $this->context);
        } catch (MigrationException $e) {
            static::assertSame(Response::HTTP_CONFLICT, $e->getStatusCode());
            static::assertSame(MigrationException::MIGRATION_PROCESSING, $e->getErrorCode());
        }
    }

    #[DataProvider('connectionProvider')]
    public function testGetDataSelection(string $connectionId, string $profileName, string $connectionName): void
    {
        $this->createConnection($connectionId, $profileName, $connectionName);

        $request = new Request(['connectionId' => $connectionId]);
        $response = $this->controller->getDataSelection($request, $this->context);
        $state = $this->jsonResponseToArray($response);

        static::assertSame($state[0]['id'], 'products');
        static::assertSame($state[0]['entityNames'][DefaultEntities::MEDIA_FOLDER], (new MediaFolderDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::PRODUCT_CUSTOM_FIELD], (new ProductAttributeDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::PRODUCT_PRICE_CUSTOM_FIELD], (new ProductPriceAttributeDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::PRODUCT_MANUFACTURER_CUSTOM_FIELD], (new ManufacturerAttributeDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::PRODUCT], (new ProductDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::PROPERTY_GROUP_OPTION], (new PropertyGroupOptionDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::PRODUCT_OPTION_RELATION], (new ProductOptionRelationDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::PRODUCT_PROPERTY_RELATION], (new ProductPropertyRelationDataSet())->getSnippet());
        static::assertSame($state[0]['entityNames'][DefaultEntities::TRANSLATION], (new TranslationDataSet())->getSnippet());

        static::assertSame($state[1]['id'], 'customersOrders');
        static::assertSame($state[1]['entityNames'][DefaultEntities::CUSTOMER_CUSTOM_FIELD], (new CustomerAttributeDataSet())->getSnippet());
        static::assertSame($state[1]['entityNames'][DefaultEntities::CUSTOMER], (new CustomerDataSet())->getSnippet());
        static::assertSame($state[1]['entityNames'][DefaultEntities::SHIPPING_METHOD], (new ShippingMethodDataSet())->getSnippet());
        static::assertSame($state[1]['entityNames'][DefaultEntities::ORDER_CUSTOM_FIELD], (new OrderAttributeDataSet())->getSnippet());
        static::assertSame($state[1]['entityNames'][DefaultEntities::ORDER], (new OrderDataSet())->getSnippet());
    }

    public function testGetDataSelectionWithoutConnectionId(): void
    {
        try {
            $this->controller->getDataSelection(new Request(), $this->context);
        } catch (RoutingException $e) {
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            static::assertSame(RoutingException::MISSING_REQUEST_PARAMETER_CODE, $e->getErrorCode());
            static::assertArrayHasKey('parameterName', $e->getParameters());
            static::assertSame($e->getParameters()['parameterName'], 'connectionId');
        }
    }

    public function testGetDataSelectionWithInvalidConnectionId(): void
    {
        $request = new Request(['connectionId' => Uuid::randomHex()]);

        try {
            $this->controller->getDataSelection($request, $this->context);
        } catch (MigrationException $e) {
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertSame(MigrationException::NO_CONNECTION_FOUND, $e->getErrorCode());
        }
    }

    public function testGetState(): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'progress' => (new MigrationProgress(0, 100, new ProgressDataSetCollection([]), 'product', 0))->jsonSerialize(),
                    'step' => MigrationStep::FETCHING->value,
                ],
            ],
            $this->context
        );

        $response = $this->controller->getState($this->context);
        $state = $this->jsonResponseToArray($response);

        static::assertSame([
            'extensions' => [],
            'step' => 'fetching',
            'progress' => 0,
            'total' => 100,
        ], $state);
    }

    public function testStartMigrationWithoutDataSelectionNames(): void
    {
        $params = [
            'dataSelectionIds' => [
                'categories_products',
                'customers_orders',
                'media',
            ],
        ];
        $requestWithToken = new Request([], $params);

        try {
            $this->controller->startMigration($requestWithToken, $this->context);
        } catch (RoutingException $e) {
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            static::assertSame(RoutingException::MISSING_REQUEST_PARAMETER_CODE, $e->getErrorCode());
            static::assertArrayHasKey('parameterName', $e->getParameters());
            static::assertSame($e->getParameters()['parameterName'], 'dataSelectionNames');
        }
    }

    public function testCheckConnection(): void
    {
        $request = new Request([], [
            'connectionId' => $this->connectionId,
        ]);

        $result = $this->controller->checkConnection($request, $this->context);
        $environmentInformation = $this->jsonResponseToArray($result);

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
            $this->controller->checkConnection($request, $this->context);
        } catch (RoutingException $e) {
            static::assertSame(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            static::assertSame(RoutingException::MISSING_REQUEST_PARAMETER_CODE, $e->getErrorCode());
            static::assertArrayHasKey('parameterName', $e->getParameters());
            static::assertSame($e->getParameters()['parameterName'], 'connectionId');
        }
    }

    public function testCheckConnectionWithInvalidConnectionId(): void
    {
        $request = new Request([], ['connectionId' => Uuid::randomHex()]);

        try {
            $this->controller->checkConnection($request, $this->context);
        } catch (MigrationException $e) {
            static::assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            static::assertSame(MigrationException::NO_CONNECTION_FOUND, $e->getErrorCode());
        }
    }

    public function testCheckConnectionWithoutCredentials(): void
    {
        $request = new Request([], ['connectionId' => $this->invalidConnectionId]);
        $response = $this->controller->checkConnection($request, $this->context);
        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $jsonResponse = $this->jsonResponseToArray($response);
        // this is not the actual expected response because of the DummyMigrationDataFetcher
        static::assertSame('Shopware', $jsonResponse['sourceSystemName']);
        static::assertSame('', $jsonResponse['requestStatus']['code']);
        static::assertSame('No error.', $jsonResponse['requestStatus']['message']);
    }

    public function testCheckConnectionWithoutFingerprint(): void
    {
        $connectionBefore = $this->connectionRepo->search(
            new Criteria([$this->connectionId]),
            $this->context
        )->getEntities()->first();

        static::assertNotNull($connectionBefore);
        static::assertNull($connectionBefore->getSourceSystemFingerprint());

        $request = new Request([], ['connectionId' => $this->connectionId]);
        $response = $this->controller->checkConnection($request, $this->context);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $jsonResponse = $this->jsonResponseToArray($response);
        static::assertSame('Shopware', $jsonResponse['sourceSystemName']);

        $connectionAfter = $this->connectionRepo->search(
            new Criteria([$this->connectionId]),
            $this->context
        )->getEntities()->first();

        static::assertNotNull($connectionAfter);
        static::assertNull($connectionAfter->getSourceSystemFingerprint());
    }

    public function testCheckConnectionWithFingerprintAndDuplicate(): void
    {
        $fingerprint = 'test-fingerprint-123';
        $duplicateConnectionId = Uuid::randomHex();

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($duplicateConnectionId, $fingerprint): void {
            $this->connectionRepo->create(
                [
                    [
                        'id' => $duplicateConnectionId,
                        'name' => 'duplicateConnection',
                        'credentialFields' => $this->connection->getCredentialFields(),
                        'profileName' => $this->connection->getProfileName(),
                        'gatewayName' => $this->connection->getGatewayName(),
                        'sourceSystemFingerprint' => $fingerprint,
                    ],
                ],
                $context
            );
        });

        $mockDataFetcher = $this->createMockDataFetcherWithFingerprint($fingerprint);
        $controller = $this->createStatusController($mockDataFetcher);

        $request = new Request([], ['connectionId' => $this->connectionId]);

        static::expectExceptionObject(MigrationException::duplicateSourceConnection());
        $controller->checkConnection($request, $this->context);
    }

    public function testCheckConnectionWithFingerprintAndNoDuplicate(): void
    {
        $fingerprint = 'unique-fingerprint-456';

        $mockDataFetcher = $this->createMockDataFetcherWithFingerprint($fingerprint);
        $controller = $this->createStatusController($mockDataFetcher);

        $request = new Request([], ['connectionId' => $this->connectionId]);
        $response = $controller->checkConnection($request, $this->context);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $jsonResponse = $this->jsonResponseToArray($response);
        static::assertSame('Shopware', $jsonResponse['sourceSystemName']);

        $connection = $this->connectionRepo->search(
            new Criteria([$this->connectionId]),
            $this->context
        )->getEntities()->first();

        static::assertNotNull($connection);
        static::assertSame($fingerprint, $connection->getSourceSystemFingerprint());
    }

    public function testCreateNewConnectionSuccess(): void
    {
        $connectionId = Uuid::randomHex();
        $connectionName = 'new connection';

        $request = new Request([], [
            'connectionId' => $connectionId,
            'connectionName' => $connectionName,
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
            'credentialFields' => $this->connection->getCredentialFields(),
        ]);

        $response = $this->controller->createNewConnection($request, $this->context);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $newConnectionEntity = $this->connectionRepo->search(
            new Criteria([$connectionId]),
            $this->context
        )->getEntities()->first();

        static::assertNotNull($newConnectionEntity);
        static::assertSame($connectionName, $newConnectionEntity->getName());
    }

    /**
     * @param array{
     *     connectionId?: string,
     *     connectionName?: string,
     *     profileName?: string,
     *     gatewayName?: string,
     *     credentialFields?: array<string, string>,
     * } $requestData
     */
    #[DataProvider('provideParamsForCreateNewConnectionToTestExceptions')]
    public function testCreateNewConnectionWithMissingParameterShouldThrowException(
        array $requestData,
        string $exceptionParameter
    ): void {
        $request = new Request([], $requestData);

        $this->expectExceptionObject(RoutingException::missingRequestParameter($exceptionParameter));
        $this->controller->createNewConnection($request, $this->context);
    }

    public static function provideParamsForCreateNewConnectionToTestExceptions(): \Generator
    {
        yield 'missing connectionId' => [
            'requestData' => [
                'connectionName' => 'new connection',
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'credentialFields' => [
                    'dbName' => 'shopware',
                ],
            ],
            'exceptionParameter' => 'connectionId',
        ];

        yield 'missing connectionName' => [
            'requestData' => [
                'connectionId' => 'example-id',
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'credentialFields' => [
                    'dbName' => 'shopware',
                ],
            ],
            'exceptionParameter' => 'connectionName',
        ];

        yield 'missing profileName' => [
            'requestData' => [
                'connectionId' => 'example-id',
                'connectionName' => 'new connection',
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'credentialFields' => [
                    'dbName' => 'shopware',
                ],
            ],
            'exceptionParameter' => 'profileName',
        ];

        yield 'missing gatewayName' => [
            'requestData' => [
                'connectionId' => 'example-id',
                'connectionName' => 'new connection',
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'credentialFields' => [
                    'dbName' => 'shopware',
                ],
            ],
            'exceptionParameter' => 'gatewayName',
        ];

        yield 'missing credentialFields' => [
            'requestData' => [
                'connectionId' => 'example-id',
                'connectionName' => 'new connection',
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
            ],
            'exceptionParameter' => 'credentialFields',
        ];
    }

    public function testCreateNewConnectionWithDuplicateNameShouldThrowException(): void
    {
        $request = new Request([], [
            'connectionId' => Uuid::randomHex(),
            'connectionName' => 'myConnection',
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
            'credentialFields' => $this->connection->getCredentialFields(),
        ]);

        $this->expectExceptionObject(MigrationException::connectionNameNotUnique());

        $this->controller->createNewConnection($request, $this->context);
    }

    public function testCreateNewConnectionWithInvalidCredentialsRollsBack(): void
    {
        $failingDataFetcher = $this->getFailingMigrationDataFetcher(
            static::getContainer()->get('swag_migration_logging.repository'),
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository'),
            static::getContainer()->get(ReaderRegistry::class)
        );
        $controller = $this->createStatusController($failingDataFetcher);

        $connectionId = Uuid::randomHex();

        $request = new Request([], [
            'connectionId' => $connectionId,
            'connectionName' => 'new connection with invalid credentials',
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'gatewayName' => DummyLocalGatewayFail::GATEWAY_NAME,
            'credentialFields' => [
                'dbName' => 'non_existent_database',
            ],
        ]);

        $throwsException = false;

        try {
            $controller->createNewConnection($request, $this->context);
        } catch (MigrationException $exception) {
            $throwsException = true;

            static::assertSame(DummyLocalGatewayFail::ERROR_CODE, $exception->getErrorCode());
            static::assertSame(DummyLocalGatewayFail::ERROR_MESSAGE, $exception->getMessage());
        }

        static::assertTrue($throwsException, 'Expected exception was not thrown.');

        $newConnectionEntity = $this->connectionRepo->search(
            new Criteria([$connectionId]),
            $this->context
        )->getEntities()->first();

        static::assertNull(
            $newConnectionEntity,
            'Connection entity should not have been created when exception was thrown.'
        );
    }

    public function testAbortMigrationWithoutRunningMigration(): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'step' => MigrationStep::ABORTED->value,
                ],
            ],
            $this->context
        );

        $this->expectExceptionObject(MigrationException::runNotFound());

        $this->controller->abortMigration($this->context);
    }

    public function testAbortMigration(): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'progress' => (new MigrationProgress(0, 100, new ProgressDataSetCollection([]), 'product', 0))->jsonSerialize(),
                    'step' => MigrationStep::FETCHING->value,
                ],
            ],
            $this->context
        );

        $response = $this->controller->abortMigration($this->context);
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $run = $this->runRepo->search(new Criteria([$this->runUuid]), $this->context)->getEntities()->first();
        static::assertNotNull($run);
        static::assertSame(MigrationStep::ABORTING, $run->getStep());
    }

    public function testFinishMigrationWithoutRunUuid(): void
    {
        $response = $this->controller->approveFinishedMigration($this->context);
        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testFinishMigration(): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'progress' => (new MigrationProgress(0, 0, new ProgressDataSetCollection([]), 'product', 0))->jsonSerialize(),
                    'step' => MigrationStep::WAITING_FOR_APPROVE->value,
                ],
            ],
            $this->context
        );

        $this->controller->approveFinishedMigration($this->context);

        $run = $this->runRepo->search(new Criteria([$this->runUuid]), $this->context)->getEntities()->first();
        static::assertNotNull($run);
        static::assertSame(MigrationStep::FINISHED, $run->getStep());
    }

    public function testResumeMigrationWithIncorrectStep(): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'step' => MigrationStep::FETCHING->value,
                ],
            ],
            $this->context
        );

        try {
            $this->controller->resumeAfterFixes($this->context);
        } catch (MigrationException $e) {
            static::assertSame(Response::HTTP_CONFLICT, $e->getStatusCode());
            static::assertSame(MigrationException::MIGRATION_NOT_IN_STEP, $e->getErrorCode());
        }
    }

    public function testResumeMigration(): void
    {
        $this->runRepo->update(
            [
                [
                    'id' => $this->runUuid,
                    'step' => MigrationStep::ERROR_RESOLUTION->value,
                ],
            ],
            $this->context
        );

        $response = $this->controller->resumeAfterFixes($this->context);
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $run = $this->runRepo->search(new Criteria([$this->runUuid]), $this->context)->getEntities()->first();
        static::assertNotNull($run);
        static::assertSame(MigrationStep::WRITING, $run->getStep());
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

    /**
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function jsonResponseToArray(?Response $response): array
    {
        static::assertNotNull($response);
        static::assertInstanceOf(JsonResponse::class, $response);
        $content = $response->getContent();
        static::assertIsNotBool($content);
        static::assertJson($content);
        $array = \json_decode($content, true);
        static::assertIsArray($array);

        return $array;
    }

    private function createStatusController(MigrationDataFetcherInterface $dataFetcher): StatusController
    {
        $salesChannelRepo = static::getContainer()->get('sales_channel.repository');
        $themeRepo = static::getContainer()->get('theme.repository');
        $mappingService = static::getContainer()->get(MappingService::class);
        $loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $migrationContextFactory = static::getContainer()->get(MigrationContextFactory::class);

        return new StatusController(
            $dataFetcher,
            new RunService(
                $this->runRepo,
                $this->connectionRepo,
                $dataFetcher,
                new DataSelectionRegistry([
                    new ProductDataSelection(),
                    new CustomerAndOrderDataSelection(),
                ]),
                $salesChannelRepo,
                $themeRepo,
                static::getContainer()->get('swag_migration_general_setting.repository'),
                static::getContainer()->get(ThemeService::class),
                $mappingService,
                static::getContainer()->get(Connection::class),
                new LoggingService($loggingRepo, new NullLogger(), new MigrationConfiguration()),
                static::getContainer()->get(TrackingEventClient::class),
                static::getContainer()->get('messenger.default_bus'),
                $migrationContextFactory,
                static::getContainer()->get(PremappingService::class),
                static::getContainer()->get(RunTransitionService::class),
                static::getContainer()->get(LogGroupingService::class),
            ),
            new DataSelectionRegistry([
                new ProductDataSelection(),
                new CustomerAndOrderDataSelection(),
            ]),
            $this->connectionRepo,
            static::getContainer()->get(ProfileRegistry::class),
            static::getContainer()->get(GatewayRegistry::class),
            $migrationContextFactory,
            $this->generalSettingRepo,
            new MigrationConnectionFactory(
                $this->connectionRepo,
                $migrationContextFactory,
                $dataFetcher,
                new MigrationFingerprintService($this->connectionRepo),
            ),
        );
    }

    private function createMockDataFetcherWithFingerprint(?string $fingerprint): MigrationDataFetcherInterface
    {
        $baseDataFetcher = $this->getMigrationDataFetcher(
            static::getContainer()->get('swag_migration_logging.repository'),
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository'),
            static::getContainer()->get(ReaderRegistry::class)
        );

        /** @var MockObject&MigrationDataFetcherInterface $mockDataFetcher */
        $mockDataFetcher = $this->createMock(MigrationDataFetcherInterface::class);

        $mockDataFetcher
            ->method('getEnvironmentInformation')
            ->willReturnCallback(static function (MigrationContextInterface $migrationContext, Context $context) use ($baseDataFetcher, $fingerprint): EnvironmentInformation {
                $environmentInformation = $baseDataFetcher->getEnvironmentInformation($migrationContext, $context);
                $environmentInformation->setFingerprint($fingerprint);

                return $environmentInformation;
            });

        return $mockDataFetcher;
    }
}
