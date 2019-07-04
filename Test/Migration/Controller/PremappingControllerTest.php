<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Controller\PremappingController;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\MigrationContextPropertyMissingException;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\MigrationContext;
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

        $gatewayRegistry = $this->getContainer()->get('SwagMigrationAssistant\Migration\Gateway\GatewayRegistry');

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
            $this->runRepo
        );

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionRepo) {
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
            'premapping' => json_decode((new JsonResponse([$this->premapping]))->getContent(), true),
        ]);

        $this->controller->writePremapping(
            $request,
            $this->context
        );

        $firstUuid = $this->mappingService->getUuid(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '0',
            $this->context
        );

        $secondUuid = $this->mappingService->getUuid(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '1',
            $this->context
        );

        static::assertSame($this->firstState->getDestinationUuid(), $firstUuid);
        static::assertSame($this->secondState->getDestinationUuid(), $secondUuid);
    }

    public function testWritePremappingWithoutRunUuid(): void
    {
        $request = new Request([], [
            'premapping' => json_decode((new JsonResponse([$this->premapping]))->getContent(), true),
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
            'premapping' => json_decode((new JsonResponse([$this->premapping]))->getContent(), true),
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
            'premapping' => json_decode((new JsonResponse([$this->premapping]))->getContent(), true),
        ]);

        $this->controller->writePremapping(
            $request,
            $this->context
        );

        $firstUuid = $this->mappingService->getUuid(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '0',
            $this->context
        );

        $secondUuid = $this->mappingService->getUuid(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '1',
            $this->context
        );

        static::assertSame($this->firstState->getDestinationUuid(), $firstUuid);
        static::assertSame($this->secondState->getDestinationUuid(), $secondUuid);

        $firstStateUuid = Uuid::randomHex();
        $firstState = new PremappingEntityStruct('0', 'First State', $firstStateUuid);

        $secondStateUuid = Uuid::randomHex();
        $secondState = new PremappingEntityStruct('1', 'Second State', $secondStateUuid);

        $premapping = new PremappingStruct(OrderStateReader::getMappingName(), [$firstState, $secondState]);

        $request = new Request([], [
            'runUuid' => $this->runUuid,
            'premapping' => json_decode((new JsonResponse([$premapping]))->getContent(), true),
        ]);
        $this->clearCacheBefore();

        $this->controller->writePremapping(
            $request,
            $this->context
        );

        $firstUuid = $this->mappingService->getUuid(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '0',
            $this->context
        );

        $secondUuid = $this->mappingService->getUuid(
            $this->connectionId,
            OrderStateReader::getMappingName(),
            '1',
            $this->context
        );

        static::assertSame($firstState->getDestinationUuid(), $firstUuid);
        static::assertSame($secondState->getDestinationUuid(), $secondUuid);
    }
}
