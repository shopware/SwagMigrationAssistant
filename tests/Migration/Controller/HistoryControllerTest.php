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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Controller\HistoryController;
use SwagMigrationAssistant\Migration\History\HistoryService;
use SwagMigrationAssistant\Migration\History\HistoryServiceInterface;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use Symfony\Component\HttpFoundation\Request;

#[Package('services-settings')]
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

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->runUuid = Uuid::randomHex();
        $this->historyService = $this->getContainer()->get(HistoryService::class);
        $this->controller = $this->getContainer()->get(HistoryController::class);
        $this->controller->setContainer($this->getContainer());
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');

        $connectionId = Uuid::randomHex();
        $connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $credentialFields = [
            'apiUser' => 'testUser',
            'apiKey' => 'testKey',
        ];
        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function () use ($connectionRepo, $connectionId): void {
            $connectionRepo->create(
                [
                    [
                        'id' => $connectionId,
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
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'connectionId' => $connectionId,
                    'credentialFields' => $credentialFields,
                    'status' => SwagMigrationRunEntity::STATUS_FINISHED,
                ],
            ],
            $this->context
        );

        $this->loggingRepo->create([
            [
                'level' => LogEntryInterface::LOG_LEVEL_ERROR,
                'code' => 'migration_error_1',
                'title' => 'Error1',
                'description' => 'Lorem Ipsum',
                'parameters' => [],
                'titleSnippet' => 'Random error snippet',
                'descriptionSnippet' => 'Lorem Ipsum random error',
                'entity' => 'product',
                'sourceId' => Uuid::randomHex(),
                'runId' => $this->runUuid,
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
        static::assertSame('Lorem Ipsum', $result->first()->get('description'));
    }

    public function testGetPrefixLogInformation(): void
    {
        $result = $this->runRepo->search(new Criteria([$this->runUuid]), $this->context);
        $run = $result->first();
        $result = $this->invokeMethod($this->historyService, 'getPrefixLogInformation', [$run]);

        static::assertIsString($result);
        static::assertStringContainsString('Migration log generated at', $result);
        static::assertStringContainsString('Run id:', $result);
        static::assertStringContainsString('Connection name: myConnection', $result);
    }

    public function testGetSuffixLogInformation(): void
    {
        $result = $this->runRepo->search(new Criteria([$this->runUuid]), $this->context);
        $run = $result->first();
        $result = $this->invokeMethod($this->historyService, 'getSuffixLogInformation', [$run]);

        static::assertIsString($result);
        static::assertStringContainsString('--------------------Additional-metadata---------------------', $result);
        static::assertStringContainsString('Environment information {JSON}:', $result);
        static::assertStringContainsString('Premapping {JSON}: ----------------------------------------------------', $result);
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
