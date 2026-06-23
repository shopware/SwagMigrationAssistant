<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\RateLimiter\Exception\RateLimitExceededException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Test\RateLimiter\DisableRateLimiterCompilerPass;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use SwagMigrationAssistant\Controller\HistoryController;
use SwagMigrationAssistant\Exception\MigrationException;
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

    private const DEFAULT_MAX_LIMIT = 500;

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

    public static function setUpBeforeClass(): void
    {
        DisableRateLimiterCompilerPass::disableNoLimit();
    }

    public static function tearDownAfterClass(): void
    {
        DisableRateLimiterCompilerPass::enableNoLimit();
    }

    protected function setUp(): void
    {
        static::getContainer()->get('cache.rate_limiter')->clear();

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

    protected function tearDown(): void
    {
        static::getContainer()->get('cache.rate_limiter')->clear();
    }

    public function testGetGroupedLogsOfRunWithoutUuid(): void
    {
        $request = new Request();

        $this->expectExceptionObject(RoutingException::missingRequestParameter('runUuid'));
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

        $this->expectExceptionObject(RoutingException::missingRequestParameter('runUuid'));
        $this->controller->downloadLogsOfRun($request, $this->context);
    }

    public function testDownloadLogsOfRun(): void
    {
        $request = new Request([], ['runUuid' => $this->runUuid]);
        $response = $this->controller->downloadLogsOfRun($request, $this->context);

        static::assertSame('text/plain', $response->headers->get('Content-type'));
    }

    public function testGetGroupedLogsOfRunIsRateLimited(): void
    {
        $request = new Request(['runUuid' => $this->runUuid]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_OAUTH_ACCESS_TOKEN_ID, 'rate-limited-token');

        for ($i = 0; $i < 30; ++$i) {
            $this->controller->getGroupedLogsOfRun($request, $this->context);
        }

        $this->expectException(RateLimitExceededException::class);
        $this->controller->getGroupedLogsOfRun($request, $this->context);
    }

    public function testDownloadLogsOfRunIsRateLimited(): void
    {
        $request = new Request([], ['runUuid' => $this->runUuid]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_OAUTH_ACCESS_TOKEN_ID, 'rate-limited-download-token');

        for ($i = 0; $i < 10; ++$i) {
            $this->controller->downloadLogsOfRun($request, $this->context);
        }

        $this->expectException(RateLimitExceededException::class);
        $this->controller->downloadLogsOfRun($request, $this->context);
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

        $this->expectExceptionObject(RoutingException::missingRequestParameter('runId'));

        $this->controller->getLogGroups($request, $this->context);
    }

    public function testGetLogGroupsWithoutLevel(): void
    {
        $request = new Request(['runId' => $this->runUuid]);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('level'));

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

    public function testGetLogEntityIdsWithoutFixWithoutRunId(): void
    {
        $request = new Request([], []);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('runId'));

        $this->controller->getLogEntityIdsWithoutFix($request);
    }

    public function testGetLogEntityIdsWithoutFixWithoutCode(): void
    {
        $request = new Request([], ['runId' => $this->runUuid]);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('code'));

        $this->controller->getLogEntityIdsWithoutFix($request);
    }

    public function testGetLogEntityIdsWithoutFixWithoutEntityName(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'TEST_CODE',
        ]);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('entityName'));

        $this->controller->getLogEntityIdsWithoutFix($request);
    }

    public function testGetLogEntityIdsWithoutFixWithoutFieldName(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'TEST_CODE',
            'entityName' => 'product',
        ]);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('fieldName'));

        $this->controller->getLogEntityIdsWithoutFix($request);
    }

    public function testGetLogEntityIdsWithoutFixReturnsEmptyWhenNoMatches(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'NON_EXISTENT_CODE',
            'entityName' => 'product',
            'fieldName' => 'name',
        ]);

        $response = $this->controller->getLogEntityIdsWithoutFix($request);

        static::assertIsString($response->getContent());
        static::assertJson($response->getContent());

        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertSame([], $json['entityIds']);
    }

    public function testGetLogEntityIdsWithoutFixReturnsMatchingIds(): void
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

        $response = $this->controller->getLogEntityIdsWithoutFix($request);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);

        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertCount(2, $json['entityIds']);
    }

    public function testGetLogEntityIdsWithoutFixWithConnectionId(): void
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

        $response = $this->controller->getLogEntityIdsWithoutFix($request);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);

        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertCount(1, $json['entityIds']);
    }

    public function testGetLogEntityIdsWithoutFixUsesDefaultLimit(): void
    {
        $entityIds = [];
        for ($i = 0; $i < 505; ++$i) {
            $entityIds[] = [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'LIMIT_TEST_CODE',
                'entityName' => 'customer',
                'fieldName' => 'email',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ];
        }

        $this->loggingRepo->create($entityIds, $this->context);

        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'LIMIT_TEST_CODE',
            'entityName' => 'customer',
            'fieldName' => 'email',
        ]);

        $response = $this->controller->getLogEntityIdsWithoutFix($request);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);

        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertCount(self::DEFAULT_MAX_LIMIT, $json['entityIds']);
    }

    public function testGetLogEntityIdsWithoutFixWithCustomLimit(): void
    {
        $entityIds = [];
        for ($i = 0; $i < 5; ++$i) {
            $entityIds[] = [
                'runId' => $this->runUuid,
                'profileName' => Shopware55Profile::PROFILE_NAME,
                'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                'code' => 'CUSTOM_LIMIT_TEST_CODE',
                'entityName' => 'order',
                'fieldName' => 'status',
                'entityId' => Uuid::randomHex(),
                'userFixable' => true,
            ];
        }

        $this->loggingRepo->create($entityIds, $this->context);

        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'CUSTOM_LIMIT_TEST_CODE',
            'entityName' => 'order',
            'fieldName' => 'status',
            'limit' => '2',
        ]);

        $response = $this->controller->getLogEntityIdsWithoutFix($request);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);

        static::assertIsArray($json);
        static::assertArrayHasKey('entityIds', $json);
        static::assertCount(2, $json['entityIds']);
    }

    #[DataProvider('provideValuesForLimitParameter')]
    public function testGetLogEntityIdsWithoutFixWithInvalidLimitValueShouldThrowException(int $limit): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'CUSTOM_LIMIT_TEST_CODE',
            'entityName' => 'order',
            'fieldName' => 'status',
            'limit' => $limit,
        ]);

        $this->expectExceptionObject(MigrationException::invalidValueForLimitParameter(self::DEFAULT_MAX_LIMIT));

        $this->controller->getLogEntityIdsWithoutFix($request);
    }

    public static function provideValuesForLimitParameter(): \Generator
    {
        yield 'with negative value' => [
            'limit' => -1,
        ];

        yield 'with value greater than default max-limit' => [
            'limit' => self::DEFAULT_MAX_LIMIT + 1,
        ];
    }

    public function testGetUnresolvedLogsBatchInformation(): void
    {
        $this->loggingRepo->create(
            [
                [
                    'runId' => $this->runUuid,
                    'profileName' => Shopware55Profile::PROFILE_NAME,
                    'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    'level' => AbstractMigrationLogEntry::LOG_LEVEL_ERROR,
                    'code' => 'GET_IDS_TEST_CODE',
                    'entityName' => 'product',
                    'fieldName' => 'description',
                    'entityId' => Uuid::randomHex(),
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
                    'entityId' => Uuid::randomHex(),
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
            ],
            $this->context
        );

        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'GET_IDS_TEST_CODE',
            'entityName' => 'product',
            'fieldName' => 'description',
        ]);

        $response = $this->controller->getUnresolvedLogsBatchInformation($request);

        static::assertIsString($response->getContent());
        $json = \json_decode($response->getContent(), true);

        static::assertIsArray($json);
        static::assertArrayHasKey('count', $json);
        static::assertArrayHasKey('limit', $json);
        static::assertSame(2, $json['count']);
        static::assertSame(500, $json['limit']);
    }

    public function testGetUnresolvedLogsBatchInformationShouldReturnNullWhenNoMatches(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'NON_EXISTENT_CODE',
            'entityName' => 'product',
            'fieldName' => 'name',
        ]);

        $response = $this->controller->getUnresolvedLogsBatchInformation($request);

        static::assertIsString($response->getContent());
        static::assertJson($response->getContent());

        $json = \json_decode($response->getContent(), true);
        static::assertIsArray($json);
        static::assertArrayHasKey('count', $json);
        static::assertArrayHasKey('limit', $json);
        static::assertSame(0, $json['count']);
        static::assertSame(500, $json['limit']);
    }

    public function testGetUnresolvedLogsBatchInformationShouldThrowErrorWithoutRunId(): void
    {
        $request = new Request([], []);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('runId'));

        $this->controller->getUnresolvedLogsBatchInformation($request);
    }

    public function testGetUnresolvedLogsBatchInformationShouldThrowErrorWithoutCode(): void
    {
        $request = new Request([], ['runId' => $this->runUuid]);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('code'));

        $this->controller->getUnresolvedLogsBatchInformation($request);
    }

    public function testGetUnresolvedLogsBatchInformationShouldThrowErrorWithoutEntityName(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'TEST_CODE',
        ]);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('entityName'));

        $this->controller->getUnresolvedLogsBatchInformation($request);
    }

    public function testGetUnresolvedLogsBatchInformationShouldThrowErrorWithoutFieldName(): void
    {
        $request = new Request([], [
            'runId' => $this->runUuid,
            'code' => 'TEST_CODE',
            'entityName' => 'product',
        ]);

        $this->expectExceptionObject(RoutingException::missingRequestParameter('fieldName'));

        $this->controller->getUnresolvedLogsBatchInformation($request);
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
