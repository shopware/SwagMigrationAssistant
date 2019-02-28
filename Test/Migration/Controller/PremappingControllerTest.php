<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Controller\PremappingController;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationNext\Migration\Premapping\PremappingReaderRegistry;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\PremappingService;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Premapping\OrderStateReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\TransactionStateReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PremappingControllerTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

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
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $stateMachineRepo = $this->getContainer()->get('state_machine.repository');
        $stateMachineStateRepo = $this->getContainer()->get('state_machine_state.repository');
        $this->mappingService = $this->getContainer()->get(MappingService::class);
        $profileUuidService = new MigrationProfileUuidService($profileRepo, Shopware55Profile::PROFILE_NAME, Shopware55LocalGateway::GATEWAY_NAME);
        $mappingRepo = $this->getContainer()->get('swag_migration_mapping.repository');

        $this->controller = new PremappingController(
            new PremappingService(
                new PremappingReaderRegistry(
                    [
                        new OrderStateReader($stateMachineRepo, $stateMachineStateRepo),
                        new TransactionStateReader($stateMachineRepo, $stateMachineStateRepo),
                    ]
                ),
                $this->mappingService,
                $mappingRepo
            ),
            $this->runRepo
        );

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) use ($connectionRepo, $profileUuidService) {
            $this->connectionId = Uuid::uuid4()->getHex();
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
                        'profileId' => $profileUuidService->getProfileUuid(),
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

        $firstStateUuid = Uuid::uuid4()->getHex();
        $this->firstState = new PremappingEntityStruct('0', 'First State', $firstStateUuid);

        $secondStateUuid = Uuid::uuid4()->getHex();
        $this->secondState = new PremappingEntityStruct('1', 'Second State', $secondStateUuid);

        $this->premapping = new PremappingStruct(OrderStateReader::getMappingName(), [$this->firstState, $this->secondState]);
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

        $firstStateUuid = Uuid::uuid4()->getHex();
        $firstState = new PremappingEntityStruct('0', 'First State', $firstStateUuid);

        $secondStateUuid = Uuid::uuid4()->getHex();
        $secondState = new PremappingEntityStruct('1', 'Second State', $secondStateUuid);

        $premapping = new PremappingStruct(OrderStateReader::getMappingName(), [$firstState, $secondState]);

        $request = new Request([], [
            'runUuid' => $this->runUuid,
            'premapping' => json_decode((new JsonResponse([$premapping]))->getContent(), true),
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

        static::assertSame($firstState->getDestinationUuid(), $firstUuid);
        static::assertSame($secondState->getDestinationUuid(), $secondUuid);
    }
}
