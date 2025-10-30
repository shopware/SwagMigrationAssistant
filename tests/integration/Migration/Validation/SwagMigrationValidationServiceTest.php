<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\integration\Migration\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingDefinition;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationInvalidFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationInvalidForeignKeyLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationMissingRequiredFieldLog;
use SwagMigrationAssistant\Migration\Validation\Log\ValidationUnexpectedFieldLog;
use SwagMigrationAssistant\Migration\Validation\SwagMigrationValidationResult;
use SwagMigrationAssistant\Migration\Validation\SwagMigrationValidationService;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(SwagMigrationValidationService::class)]
class SwagMigrationValidationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const CONNECTION_ID = '01991554142d73348ea58793d98f1989';

    private SwagMigrationValidationService $validationService;

    /**
     * @var EntityRepository<SwagMigrationLoggingCollection>
     */
    private EntityRepository $loggingRepo;

    /**
     * @var EntityRepository<SwagMigrationRunCollection>
     */
    private EntityRepository $runRepo;

    /**
     * @var EntityRepository<SwagMigrationMappingCollection>
     */
    private EntityRepository $mappingRepo;

    private Context $context;

    private string $runId;

    protected function setUp(): void
    {
        $this->validationService = static::getContainer()->get(SwagMigrationValidationService::class);
        $this->loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->mappingRepo = static::getContainer()->get(SwagMigrationMappingDefinition::ENTITY_NAME . '.repository');
        $this->context = Context::createDefaultContext();

        $this->runId = Uuid::randomHex();
        static::getContainer()->get('swag_migration_connection.repository')->create(
            [
                [
                    'id' => self::CONNECTION_ID,
                    'name' => 'test connection',
                    'profileName' => Shopware54Profile::PROFILE_NAME,
                    'gatewayName' => DummyLocalGateway::GATEWAY_NAME,
                ],
            ],
            $this->context
        );

        $this->runRepo->create(
            [
                [
                    'id' => $this->runId,
                    'step' => MigrationStep::FETCHING->value,
                    'connectionId' => self::CONNECTION_ID,
                ],
            ],
            $this->context
        );
    }

    public function testShouldEarlyReturnNullWhenConvertedDataIsEmpty(): void
    {
        $migrationContext = new MigrationContext(
            new SwagMigrationConnectionEntity(),
            new Shopware54Profile(),
            new DummyLocalGateway(),
            null,
            $this->runId,
        );

        static::assertNull($this->validationService->validate(
            $migrationContext,
            $this->context,
            [],
            ProductDefinition::ENTITY_NAME
        ));
        static::assertNull($this->validationService->validate(
            $migrationContext,
            $this->context,
            null,
            ProductDefinition::ENTITY_NAME
        ));
    }

    /**
     * @param array<string, mixed> $convertedData
     * @param array<class-string> $expectedLogs
     */
    #[DataProvider('entityStructureAndFieldProvider')]
    public function testShouldValidateStructureAndFieldsValues(array $convertedData, array $expectedLogs): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(self::CONNECTION_ID);
        $connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $connection->setGatewayName(DummyLocalGateway::GATEWAY_NAME);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware54Profile(),
            new DummyLocalGateway(),
            null,
            $this->runId,
        );

        $result = $this->validationService->validate(
            $migrationContext,
            $this->context,
            $convertedData,
            SwagMigrationLoggingDefinition::ENTITY_NAME
        );

        static::assertInstanceOf(SwagMigrationValidationResult::class, $result);
        static::assertSame(SwagMigrationLoggingDefinition::ENTITY_NAME, $result->getEntityName());

        $this->clearCacheData();

        $logs = $this->loggingRepo->search(new Criteria(), $this->context)->getEntities();
        static::assertInstanceOf(SwagMigrationLoggingCollection::class, $logs);

        static::assertCount(\count($expectedLogs), $logs);
        static::assertCount(\count($expectedLogs), $result->getLogs());

        $logCodes = array_map(fn ($log) => $log::class, $result->getLogs());
        static::assertSame($expectedLogs, $logCodes);
    }

    /**
     * @param array<string, mixed> $convertedData
     * @param array<int, array<string, mixed>> $mappings
     * @param array<class-string> $expectedLogs
     */
    #[DataProvider('associationProvider')]
    public function testValidateAssociations(array $convertedData, array $mappings, array $expectedLogs): void
    {
        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(self::CONNECTION_ID);
        $connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $connection->setGatewayName(DummyLocalGateway::GATEWAY_NAME);

        $migrationContext = new MigrationContext(
            $connection,
            new Shopware54Profile(),
            new DummyLocalGateway(),
            null,
            $this->runId,
        );

        if (!empty($mappings)) {
            $this->mappingRepo->create($mappings, $this->context);
        }

        $result = $this->validationService->validate(
            $migrationContext,
            $this->context,
            $convertedData,
            SwagMigrationLoggingDefinition::ENTITY_NAME
        );

        static::assertInstanceOf(SwagMigrationValidationResult::class, $result);

        $logClasses = array_map(static fn ($log) => $log::class, $result->getLogs());
        static::assertEquals($expectedLogs, $logClasses);
    }

    public static function entityStructureAndFieldProvider(): \Generator
    {
        $log = [
            'id' => Uuid::randomHex(),
            'profileName' => 'profile',
            'gatewayName' => 'gateway',
            'level' => 'error',
            'code' => 'some_code',
            'userFixable' => true,
            'sourceData' => [
                'some' => 'data',
            ],
            'createdAt' => (new \DateTime())->format(\DATE_ATOM),
        ];

        yield 'valid' => [
            $log,
            [],
        ];

        yield 'structure - missing required fields' => [
            [
                'id' => Uuid::randomHex(),
                'level' => 'error',
                'code' => 'some_code',
                'userFixable' => true,
                'createdAt' => (new \DateTime())->format(\DATE_ATOM),
            ],
            [
                ValidationMissingRequiredFieldLog::class,
                ValidationMissingRequiredFieldLog::class,
            ],
        ];

        yield 'structure - unexpected fields' => [
            [
                ...$log,
                'unexpectedField1' => 'value',
                'unexpectedField2' => 'value',
            ],
            [
                ValidationUnexpectedFieldLog::class,
                ValidationUnexpectedFieldLog::class,
            ],
        ];

        yield 'fields - invalid type' => [
            [
                ...$log,
                'userFixable' => 'not_a_boolean',
            ],
            [
                ValidationInvalidFieldValueLog::class,
            ],
        ];

        yield 'fields - too long' => [
            [
                ...$log,
                'code' => str_repeat('sw', 128),
            ],
            [
                ValidationInvalidFieldValueLog::class,
            ],
        ];

        yield 'fields - invalid uuid' => [
            [
                ...$log,
                'id' => 'not-a-uuid',
            ],
            [
                ValidationInvalidFieldValueLog::class,
            ],
        ];

        yield 'fields - invalid json' => [
            [
                ...$log,
                'sourceData' => "\xB1\x31",
            ],
            [
                ValidationInvalidFieldValueLog::class,
            ],
        ];

        yield 'structure/field - multiple errors' => [
            [
                'id' => Uuid::randomHex(),
                'gatewayName' => true,
                'level' => 1,
                'code' => ['sw'],
                'userFixable' => true,
                'createdAt' => (new \DateTime())->format(\DATE_ATOM),
                'unexpectedField' => 'value',
            ],
            [
                ValidationMissingRequiredFieldLog::class,
                ValidationUnexpectedFieldLog::class,
                ValidationInvalidFieldValueLog::class,
                ValidationInvalidFieldValueLog::class,
                ValidationInvalidFieldValueLog::class,
            ],
        ];
    }

    public static function associationProvider(): \Generator
    {
        $log = [
            'id' => Uuid::randomHex(),
            'profileName' => 'profile',
            'gatewayName' => 'gateway',
            'level' => 'error',
            'code' => 'some_code',
            'userFixable' => true,
            'sourceData' => [
                'some' => 'data',
            ],
            'createdAt' => (new \DateTime())->format(\DATE_ATOM),
        ];

        $runId = Uuid::randomHex();
        $mapping = [
            'id' => Uuid::randomHex(),
            'connectionId' => self::CONNECTION_ID,
            'entity' => SwagMigrationRunDefinition::ENTITY_NAME,
            'oldIdentifier' => $runId,
            'entityUuid' => $runId,
        ];

        yield 'valid fk' => [
            [
                ...$log,
                'runId' => $runId,
            ],
            [$mapping],
            [],
        ];

        yield 'invalid fk' => [
            [
                ...$log,
                'runId' => Uuid::randomHex(),
            ],
            [$mapping],
            [ValidationInvalidForeignKeyLog::class],
        ];

        yield 'fk field not in converted data' => [
            $log,
            [],
            [],
        ];

        yield 'fk value is null' => [
            [
                ...$log,
                'runId' => null,
            ],
            [],
            [
                ValidationInvalidFieldValueLog::class,
            ],
        ];

        yield 'fk value is empty string' => [
            [
                ...$log,
                'runId' => '',
            ],
            [],
            [
                ValidationInvalidFieldValueLog::class,
            ],
        ];
    }
}
