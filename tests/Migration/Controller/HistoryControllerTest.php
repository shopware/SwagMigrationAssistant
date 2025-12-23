<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Controller\HistoryController;
use SwagMigrationAssistant\Migration\History\HistoryService;
use SwagMigrationAssistant\Migration\History\HistoryServiceInterface;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\HttpFoundation\Request;

#[Package('fundamentals@after-sales')]
class HistoryControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private HistoryController $controller;

    private string $runUuid;

    private Context $context;

    /**
     * @var EntityRepository<SwagMigrationLoggingCollection>
     */
    private EntityRepository $loggingRepo;

    private HistoryServiceInterface $historyService;

    /**
     * @var EntityRepository<SwagMigrationRunCollection>
     */
    private EntityRepository $runRepo;

    private string $connectionId;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->runUuid = Uuid::randomHex();
        $this->historyService = static::getContainer()->get(HistoryService::class);
        $this->controller = static::getContainer()->get(HistoryController::class);
        $this->controller->setContainer(static::getContainer());
        $this->loggingRepo = static::getContainer()->get('swag_migration_logging.repository');

        $this->connectionId = Uuid::randomHex();
        $connectionRepo = static::getContainer()->get('swag_migration_connection.repository');

        $credentialFields = [
            'apiUser' => 'testUser',
            'apiKey' => 'testKey',
        ];

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function () use ($connectionRepo): void {
            $connectionRepo->create(
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
                $this->context
            );
        });
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'connectionId' => $this->connectionId,
                    'credentialFields' => $credentialFields,
                    'step' => MigrationStep::FINISHED->value,
                ],
            ],
            $this->context
        );

        $this->loggingRepo->create([
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'migration_error_1',
                'userFixable' => false,
            ],
        ], $this->context);
    }

    public function testGetGroupedLogsOfRunWithoutUuid(): void
    {
        $request = new Request();

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "runUuid" is missing.');
        $this->controller->getGroupedLogsOfRun($request, $this->context);
    }

    public function testGetGroupedLogsOfRun(): void
    {
        $request = new Request(['runUuid' => $this->runUuid], []);
        $response = $this->controller->getGroupedLogsOfRun($request, $this->context);

        static::assertIsString($response->getContent());
        static::assertJson($response->getContent());

        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);
        static::assertArrayHasKey('total', $json);
        static::assertArrayHasKey('items', $json);
        static::assertArrayHasKey('downloadUrl', $json);

        static::assertSame(1, $json['total']);
    }

    public function testDownloadLogsOfRunWithoutUuid(): void
    {
        $request = new Request();

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "runUuid" is missing.');
        $this->controller->downloadLogsOfRun($request, $this->context);
    }

    public function testDownloadLogsOfRun(): void
    {
        $request = new Request([], ['runUuid' => $this->runUuid]);
        $response = $this->controller->downloadLogsOfRun($request, $this->context);

        static::assertSame('text/plain', $response->headers->get('Content-type'));
    }

    public function testGetLogChunk(): void
    {
        $result = $this->invokeMethod($this->historyService, 'getLogChunk', [$this->runUuid, 0, $this->context]);

        static::assertInstanceOf(SwagMigrationLoggingCollection::class, $result);
        static::assertNotNull($result->first());
    }

    public function testGetLogGroupsWithoutRunId(): void
    {
        $request = new Request();

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "runId" is missing.');

        $this->controller->getLogGroups($request, $this->context);
    }

    public function testGetLogGroupsWithoutLevel(): void
    {
        $request = new Request(['runId' => $this->runUuid]);

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "level" is missing.');

        $this->controller->getLogGroups($request, $this->context);
    }

    public function testGetLogGroupsReturnsEmptyWhenNoUserFixableLogs(): void
    {
        $request = new Request([
            'runId' => $this->runUuid,
            'level' => 'error',
        ]);

        $response = $this->controller->getLogGroups($request, $this->context);

        static::assertIsString($response->getContent());
        static::assertJson($response->getContent());

        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);
        static::assertArrayHasKey('total', $json);
        static::assertArrayHasKey('items', $json);
        static::assertArrayHasKey('levelCounts', $json);

        static::assertSame(0, $json['total']);
        static::assertSame([], $json['items']);
    }

    public function testGetLogGroupsReturnsGroupedLogs(): void
    {
        $this->loggingRepo->create([
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'MISSING_REQUIRED_FIELD',
                'entityName' => 'product',
                'fieldName' => 'name',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'MISSING_REQUIRED_FIELD',
                'entityName' => 'product',
                'fieldName' => 'name',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_WARNING,
                'code' => 'INVALID_FORMAT',
                'entityName' => 'customer',
                'fieldName' => 'email',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
        ], $this->context);

        $request = new Request([
            'runId' => $this->runUuid,
            'level' => 'error',
        ]);

        $response = $this->controller->getLogGroups($request, $this->context);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);

        static::assertSame(1, $json['total']);
        static::assertCount(1, $json['items']);

        $item = $json['items'][0];
        static::assertSame('MISSING_REQUIRED_FIELD', $item['code']);
        static::assertSame('product', $item['entityName']);
        static::assertSame('name', $item['fieldName']);
        static::assertSame(2, $item['count']);

        static::assertArrayHasKey('levelCounts', $json);
        static::assertSame(1, $json['levelCounts']['error']);
        static::assertSame(1, $json['levelCounts']['warning']);
    }

    public function testGetLogGroupsWithPagination(): void
    {
        $this->loggingRepo->create([
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'ERROR_CODE_1',
                'entityName' => 'product',
                'fieldName' => 'name',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'ERROR_CODE_2',
                'entityName' => 'customer',
                'fieldName' => 'email',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'ERROR_CODE_3',
                'entityName' => 'order',
                'fieldName' => 'status',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
        ], $this->context);

        $request = new Request([
            'runId' => $this->runUuid,
            'level' => 'error',
            'page' => '1',
            'limit' => '2',
        ]);

        $response = $this->controller->getLogGroups($request, $this->context);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);

        static::assertSame(3, $json['total']);
        static::assertCount(2, $json['items']);
    }

    public function testGetLogGroupsWithFilters(): void
    {
        $this->loggingRepo->create([
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'FILTER_TEST_CODE',
                'entityName' => 'product',
                'fieldName' => 'name',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'OTHER_CODE',
                'entityName' => 'customer',
                'fieldName' => 'email',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
        ], $this->context);

        $request = new Request([
            'runId' => $this->runUuid,
            'level' => 'error',
            'filterCode' => 'FILTER_TEST_CODE',
        ]);

        $response = $this->controller->getLogGroups($request, $this->context);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);

        static::assertSame(1, $json['total']);
        static::assertCount(1, $json['items']);
        static::assertSame('FILTER_TEST_CODE', $json['items'][0]['code']);
    }

    public function testGetAllEntityIdsWithoutRunId(): void
    {
        $request = new Request([], []);

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "runId" is missing.');

        $this->controller->getAllEntityIds($request);
    }

    public function testGetAllEntityIdsWithoutCode(): void
    {
        $request = new Request([], ['runId' => $this->runUuid]);

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "code" is missing.');

        $this->controller->getAllEntityIds($request);
    }

    public function testGetAllEntityIdsWithoutEntityName(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'TEST_CODE',
        ]);

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "entityName" is missing.');

        $this->controller->getAllEntityIds($request);
    }

    public function testGetAllEntityIdsWithoutFieldName(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'TEST_CODE',
            'entityName' => 'product',
        ]);

        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Parameter "fieldName" is missing.');

        $this->controller->getAllEntityIds($request);
    }

    public function testGetAllEntityIdsReturnsEmptyWhenNoMatches(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'NON_EXISTENT_CODE',
            'entityName' => 'product',
            'fieldName' => 'name',
        ]);

        $response = $this->controller->getAllEntityIds($request);

        static::assertIsString($response->getContent());
        static::assertJson($response->getContent());

        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertSame([], $json['entityIds']);
    }

    public function testGetAllEntityIdsReturnsMatchingIds(): void
    {
        $entityId1 = Uuid::randomHex();
        $entityId2 = Uuid::randomHex();

        $this->loggingRepo->create([
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'GET_IDS_TEST_CODE',
                'entityName' => 'product',
                'fieldName' => 'description',
                'entityId' => $entityId1,
                'userFixable' => true,
            ],
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'GET_IDS_TEST_CODE',
                'entityName' => 'product',
                'fieldName' => 'description',
                'entityId' => $entityId2,
                'userFixable' => true,
            ],
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'GET_IDS_TEST_CODE',
                'entityName' => 'customer',
                'fieldName' => 'description',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ],
        ], $this->context);

        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'GET_IDS_TEST_CODE',
            'entityName' => 'product',
            'fieldName' => 'description',
        ]);

        $response = $this->controller->getAllEntityIds($request);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);

        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertCount(2, $json['entityIds']);
    }

    public function testGetAllEntityIdsWithConnectionId(): void
    {
        $entityId = Uuid::randomHex();

        $this->loggingRepo->create([
            [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'CONNECTION_ID_TEST',
                'entityName' => 'order',
                'fieldName' => 'status',
                'entityId' => $entityId,
                'userFixable' => true,
            ],
        ], $this->context);

        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'CONNECTION_ID_TEST',
            'entityName' => 'order',
            'fieldName' => 'status',
            'connectionId' => $this->connectionId,
        ]);

        $response = $this->controller->getAllEntityIds($request);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);

        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertCount(1, $json['entityIds']);
    }

    /**
     * @param array<Context|string|int|Entity|null> $parameters
     *
     * @return HistoryService|string|SwagMigrationLoggingCollection
     */
    public function invokeMethod(object $object, string $methodName, array $parameters)
    {
        $reflection = new \ReflectionClass($object::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
