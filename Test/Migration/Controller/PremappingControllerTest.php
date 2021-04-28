<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Controller\PremappingController;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactory;
use SwagMigrationAssistant\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationAssistant\Migration\Premapping\PremappingReaderRegistry;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\PremappingService;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\TransactionStateReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PremappingControllerTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var PremappingController
     */
    private $controller;

    /**
     * @var PremappingStruct
     */
    private $premapping;

    /**
     * @var MappingService
     */
    private $mappingService;

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var PremappingEntityStruct
     */
    private $firstState;

    /**
     * @var PremappingEntityStruct
     */
    private $secondState;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $stateMachineRepo = $this->getContainer()->get('state_machine.repository');
        $stateMachineStateRepo = $this->getContainer()->get('state_machine_state.repository');
        $this->mappingService = $this->getContainer()->get(MappingService::class);
        $mappingRepo = $this->getContainer()->get('swag_migration_mapping.repository');
        $migrationContextFactory = $this->getContainer()->get(MigrationContextFactory::class);

        $gatewayRegistry = $this->getContainer()->get(GatewayRegistry::class);

        $this->controller = new PremappingController(
            new PremappingService(
                new PremappingReaderRegistry(
                    [
                        new OrderStateReader($stateMachineRepo, $stateMachineStateRepo, $gatewayRegistry),
                        new TransactionStateReader($stateMachineRepo, $stateMachineStateRepo, $gatewayRegistry),
                    ]
                ),
                $this->mappingService,
                $mappingRepo,
                $runRepo,
                $connectionRepo
            ),
            $this->runRepo,
            $migrationContextFactory
        );

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionRepo): void {
            $this->connectionId = Uuid::randomHex();
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

        $firstStateUuid = Uuid::randomHex();
        $this->firstState = new PremappingEntityStruct('0', 'First State', $firstStateUuid);

        $secondStateUuid = Uuid::randomHex();
        $this->secondState = new PremappingEntityStruct('1', 'Second State', $secondStateUuid);

        $this->premapping = new PremappingStruct(OrderStateReader::getMappingName(), [$this->firstState, $this->secondState]);
    }

    public function testGeneratePremappingWithoutRunUuid(): void
    {
        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->generatePremapping(
            new Request(),
            $this->context
        );
    }

    public function testGeneratePremappingWithInvalidRunUuid(): void
    {
        $this->expectException(EntityNotExistsException::class);
        $this->controller->generatePremapping(
            new Request([], ['runUuid' => Uuid::randomHex()]),
            $this->context
        );
    }

    public function testWritePremapping(): void
    {
        $request = new Request([], [
            'runUuid' => $this->runUuid,
            'premapping' => \json_decode((string) (new JsonResponse([$this->premapping]))->getContent(), true),
        ]);

        $this->controller->writePremapping(
            $request,
            $this->context
        );

        $firstMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '0',
            $this->context
        );

        $secondMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '1',
            $this->context
        );

        static::assertNotNull($firstMapping);
        static::assertNotNull($secondMapping);
        static::assertSame($this->firstState->getDestinationUuid(), $firstMapping['entityUuid']);
        static::assertSame($this->secondState->getDestinationUuid(), $secondMapping['entityUuid']);
    }

    public function testWritePremappingWithoutRunUuid(): void
    {
        $request = new Request([], [
            'premapping' => \json_decode((string) (new JsonResponse([$this->premapping]))->getContent(), true),
        ]);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->writePremapping(
            $request,
            $this->context
        );
    }

    public function testWritePremappingWithoutPremapping(): void
    {
        $request = new Request([], [
            'runUuid' => $this->runUuid,
        ]);

        $this->expectException(MigrationContextPropertyMissingException::class);
        $this->controller->writePremapping(
            $request,
            $this->context
        );
    }

    public function testWritePremappingWithInvalidRunUuid(): void
    {
        $request = new Request([], [
            'runUuid' => Uuid::randomHex(),
            'premapping' => \json_decode((string) (new JsonResponse([$this->premapping]))->getContent(), true),
        ]);

        $this->expectException(EntityNotExistsException::class);
        $this->controller->writePremapping(
            $request,
            $this->context
        );
    }

    public function testWritePremappingTwice(): void
    {
        $request = new Request([], [
            'runUuid' => $this->runUuid,
            'premapping' => \json_decode((string) (new JsonResponse([$this->premapping]))->getContent(), true),
        ]);

        $this->controller->writePremapping(
            $request,
            $this->context
        );

        $firstMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '0',
            $this->context
        );

        $secondMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '1',
            $this->context
        );

        static::assertNotNull($firstMapping);
        static::assertNotNull($secondMapping);
        static::assertSame($this->firstState->getDestinationUuid(), $firstMapping['entityUuid']);
        static::assertSame($this->secondState->getDestinationUuid(), $secondMapping['entityUuid']);

        $firstStateUuid = Uuid::randomHex();
        $firstState = new PremappingEntityStruct('0', 'First State', $firstStateUuid);

        $secondStateUuid = Uuid::randomHex();
        $secondState = new PremappingEntityStruct('1', 'Second State', $secondStateUuid);

        $premapping = new PremappingStruct(OrderStateReader::getMappingName(), [$firstState, $secondState]);

        $request = new Request([], [
            'runUuid' => $this->runUuid,
            'premapping' => \json_decode((string) (new JsonResponse([$premapping]))->getContent(), true),
        ]);
        $this->clearCacheData();

        $this->controller->writePremapping(
            $request,
            $this->context
        );

        $firstMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '0',
            $this->context
        );

        $secondMapping = $this->mappingService->getMapping(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '1',
            $this->context
        );

        static::assertNotNull($firstMapping);
        static::assertNotNull($secondMapping);
        static::assertSame($firstState->getDestinationUuid(), $firstMapping['entityUuid']);
        static::assertSame($secondState->getDestinationUuid(), $secondMapping['entityUuid']);
    }
}
