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
use Shopware\Core\Defaults;
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
use SwagMigrationAssistant\Migration\Validation\Exception\MigrationValidationException;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationExceptionLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidAssociationLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidOptionalFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidRequiredFieldValueLog;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationInvalidRequiredTranslation;
use SwagMigrationAssistant\Migration\Validation\Log\MigrationValidationMissingRequiredFieldLog;
use SwagMigrationAssistant\Migration\Validation\MigrationValidationResult;
use SwagMigrationAssistant\Migration\Validation\MigrationValidationService;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(MigrationValidationService::class)]
class MigrationValidationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const CONNECTION_ID = '01991554142d73348ea58793d98f1989';

    private MigrationContext $migrationContext;

    private MigrationValidationService $validationService;

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
        $this->validationService = static::getContainer()->get(MigrationValidationService::class);
        $this->loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->mappingRepo = static::getContainer()->get(SwagMigrationMappingDefinition::ENTITY_NAME . '.repository');
        $this->context = Context::createDefaultContext();

        $connection = new SwagMigrationConnectionEntity();
        $connection->setId(self::CONNECTION_ID);
        $connection->setProfileName(Shopware54Profile::PROFILE_NAME);
        $connection->setGatewayName(DummyLocalGateway::GATEWAY_NAME);

        $this->runId = Uuid::randomHex();

        $this->migrationContext = new MigrationContext(
            $connection,
            new Shopware54Profile(),
            new DummyLocalGateway(),
            null,
            $this->runId,
        );

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
            ProductDefinition::ENTITY_NAME,
            []
        ));
        static::assertNull($this->validationService->validate(
            $migrationContext,
            $this->context,
            null,
            ProductDefinition::ENTITY_NAME,
            []
        ));
    }

    /**
     * @param array<string, mixed> $convertedData
     * @param array<class-string> $expectedLogs
     */
    #[DataProvider('entityStructureAndFieldProvider')]
    public function testShouldValidateStructureAndFieldsValues(array $convertedData, array $expectedLogs): void
    {
        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            SwagMigrationLoggingDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);
        static::assertSame(SwagMigrationLoggingDefinition::ENTITY_NAME, $result->getEntityName());

        $this->clearCacheData();

        $logs = $this->loggingRepo->search(new Criteria(), $this->context)->getEntities();
        static::assertInstanceOf(SwagMigrationLoggingCollection::class, $logs);

        static::assertCount(\count($expectedLogs), $logs);
        static::assertCount(\count($expectedLogs), $result->getLogs());

        $logCodes = \array_map(fn ($log) => $log::class, $result->getLogs());
        static::assertSame($expectedLogs, $logCodes);
    }

    public function testShouldFilterNullableFields(): void
    {
        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            [
                'id' => Uuid::randomHex(),
            ],
            ProductDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);

        $missingFields = \array_map(fn ($log) => $log->getFieldName(), $result->getLogs());

        // Only 'stock' should be required as its not nullable in db and has no default
        static::assertCount(1, $missingFields);
        static::assertContains('stock', $missingFields);
    }

    /**
     * @param array<string, mixed> $convertedData
     */
    #[DataProvider('invalidIdProvider')]
    public function testShouldLogWhenEntityHasInvalidOrMissingId(array $convertedData, string $expectedExceptionMessage): void
    {
        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            SwagMigrationLoggingDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);

        $logs = \array_filter($result->getLogs(), fn ($log) => $log instanceof MigrationValidationExceptionLog);
        static::assertCount(1, $logs);

        $exceptionLog = array_values($logs)[0];
        static::assertInstanceOf(MigrationValidationExceptionLog::class, $exceptionLog);

        static::assertSame($expectedExceptionMessage, $exceptionLog->getExceptionMessage());
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, string}>
     */
    public static function invalidIdProvider(): \Generator
    {
        $baseData = [
            'level' => 'error',
            'code' => 'some_code',
            'userFixable' => true,
            'createdAt' => (new \DateTime())->format(\DATE_ATOM),
        ];

        yield 'missing id (null)' => [
            $baseData,
            MigrationValidationException::unexpectedNullValue('id')->getMessage(),
        ];

        yield 'invalid uuid string' => [
            [...$baseData, 'id' => 'invalid-uuid'],
            MigrationValidationException::invalidId('invalid-uuid', SwagMigrationLoggingDefinition::ENTITY_NAME)->getMessage(),
        ];

        yield 'integer id instead of uuid string' => [
            [...$baseData, 'id' => 12345],
            MigrationValidationException::invalidId('12345', SwagMigrationLoggingDefinition::ENTITY_NAME)->getMessage(),
        ];

        yield 'empty string id' => [
            [...$baseData, 'id' => ''],
            MigrationValidationException::invalidId('', SwagMigrationLoggingDefinition::ENTITY_NAME)->getMessage(),
        ];
    }

    /**
     * @param array<string, mixed> $convertedData
     * @param array<int, array<string, mixed>> $mappings
     * @param array<class-string> $expectedLogs
     */
    #[DataProvider('associationProvider')]
    public function testValidateAssociations(array $convertedData, array $mappings, array $expectedLogs): void
    {
        if (!empty($mappings)) {
            $this->mappingRepo->create($mappings, $this->context);
        }

        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            SwagMigrationLoggingDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);

        $logClasses = \array_map(static fn ($log) => $log::class, $result->getLogs());
        static::assertEquals($expectedLogs, $logClasses);
    }

    public function testMissingTranslationAssociation(): void
    {
        $convertedData = [
            'id' => Uuid::randomHex(),
            'versionId' => Uuid::randomHex(),
            'stock' => 10,
            'translations' => ['lel'],
        ];

        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            ProductDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);

        $logClasses = \array_map(static fn ($log) => $log::class, $result->getLogs());
        static::assertCount(1, $logClasses);
        static::assertEquals([MigrationValidationInvalidRequiredTranslation::class], $logClasses);
    }

    public function testValidTranslationAssociation(): void
    {
        $convertedData = [
            'id' => Uuid::randomHex(),
            'versionId' => Uuid::randomHex(),
            'stock' => 10,
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'Valid name',
                ],
            ],
        ];

        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            ProductDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);

        $logClasses = \array_map(static fn ($log) => $log::class, $result->getLogs());
        static::assertCount(0, $logClasses);
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
                MigrationValidationMissingRequiredFieldLog::class,
                MigrationValidationMissingRequiredFieldLog::class,
            ],
        ];

        yield 'fields - invalid type' => [
            [
                ...$log,
                'userFixable' => 'not_a_boolean',
            ],
            [
                MigrationValidationInvalidOptionalFieldValueLog::class,
            ],
        ];

        yield 'fields - too long' => [
            [
                ...$log,
                'code' => str_repeat('sw', 128),
            ],
            [
                MigrationValidationInvalidRequiredFieldValueLog::class,
            ],
        ];

        yield 'fields - invalid uuid' => [
            [
                ...$log,
                'id' => 'not-a-uuid',
            ],
            [
                MigrationValidationExceptionLog::class,
            ],
        ];

        yield 'fields - invalid json' => [
            [
                ...$log,
                'sourceData' => "\xB1\x31",
            ],
            [
                MigrationValidationInvalidOptionalFieldValueLog::class,
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
            ],
            [
                MigrationValidationMissingRequiredFieldLog::class,
                MigrationValidationInvalidRequiredFieldValueLog::class,
                MigrationValidationInvalidRequiredFieldValueLog::class,
                MigrationValidationInvalidRequiredFieldValueLog::class,
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
            'entityId' => $runId,
        ];

        yield 'valid fk' => [
            [
                ...$log,
                'runId' => $runId,
            ],
            [$mapping],
            [],
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
                MigrationValidationInvalidOptionalFieldValueLog::class,
            ],
        ];

        yield 'fk value is empty string' => [
            [
                ...$log,
                'runId' => '',
            ],
            [],
            [
                MigrationValidationInvalidOptionalFieldValueLog::class,
            ],
        ];
    }

    /**
     * Tests for ManyToMany and OneToMany association validation.
     *
     * @return \Generator<string, array{array<string, mixed>, array<class-string>}>
     */
    public static function toManyAssociationProvider(): \Generator
    {
        $baseProduct = [
            'id' => Uuid::randomHex(),
            'versionId' => Uuid::randomHex(),
            'stock' => 10,
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'Test Product',
                ],
            ],
        ];

        yield 'valid categories association (empty array)' => [
            [
                ...$baseProduct,
                'categories' => [],
            ],
            [],
        ];

        yield 'valid categories association (with valid entries)' => [
            [
                ...$baseProduct,
                'categories' => [
                    ['id' => Uuid::randomHex()],
                    ['id' => Uuid::randomHex()],
                ],
            ],
            [],
        ];

        yield 'invalid categories association (non-array value)' => [
            [
                ...$baseProduct,
                'categories' => 'not-an-array',
            ],
            [
                MigrationValidationInvalidAssociationLog::class,
            ],
        ];

        yield 'invalid categories association (entry is not array)' => [
            [
                ...$baseProduct,
                'categories' => [
                    'not-an-array-entry',
                ],
            ],
            [
                MigrationValidationInvalidAssociationLog::class,
            ],
        ];

        yield 'invalid categories association (invalid UUID in entry)' => [
            [
                ...$baseProduct,
                'categories' => [
                    ['id' => 'invalid-uuid'],
                ],
            ],
            [
                MigrationValidationInvalidAssociationLog::class,
            ],
        ];

        yield 'invalid categories association (multiple errors)' => [
            [
                ...$baseProduct,
                'categories' => [
                    ['id' => Uuid::randomHex()],
                    'invalid-entry',
                    ['id' => 'invalid-uuid'],
                ],
            ],
            [
                // Only first error is logged since validation throws on first failure
                MigrationValidationInvalidAssociationLog::class,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $convertedData
     * @param array<class-string> $expectedLogs
     */
    #[DataProvider('toManyAssociationProvider')]
    public function testValidateToManyAssociations(array $convertedData, array $expectedLogs): void
    {
        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            ProductDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);

        $logClasses = \array_map(static fn ($log) => $log::class, $result->getLogs());
        static::assertEquals($expectedLogs, $logClasses);
    }

    /**
     * Tests for ManyToOne and OneToOne association validation.
     *
     * @return \Generator<string, array{array<string, mixed>, array<class-string>}>
     */
    public static function toOneAssociationProvider(): \Generator
    {
        $baseProduct = [
            'id' => Uuid::randomHex(),
            'versionId' => Uuid::randomHex(),
            'stock' => 10,
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'Test Product',
                ],
            ],
        ];

        yield 'valid manufacturer association (null value)' => [
            $baseProduct,
            [],
        ];

        yield 'valid manufacturer association (with valid id)' => [
            [
                ...$baseProduct,
                'manufacturer' => ['id' => Uuid::randomHex(), 'name' => 'Test Manufacturer'],
            ],
            [],
        ];

        yield 'invalid manufacturer association (non-array value)' => [
            [
                ...$baseProduct,
                'manufacturer' => 'not-an-array',
            ],
            [
                MigrationValidationInvalidAssociationLog::class,
            ],
        ];

        yield 'invalid manufacturer association (invalid UUID)' => [
            [
                ...$baseProduct,
                'manufacturer' => ['id' => 'invalid-uuid'],
            ],
            [
                MigrationValidationInvalidAssociationLog::class,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $convertedData
     * @param array<class-string> $expectedLogs
     */
    #[DataProvider('toOneAssociationProvider')]
    public function testValidateToOneAssociations(array $convertedData, array $expectedLogs): void
    {
        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            ProductDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);

        $logClasses = \array_map(static fn ($log) => $log::class, $result->getLogs());
        static::assertEquals($expectedLogs, $logClasses);
    }

    public function testShouldReturnNullWhenEntityDefinitionDoesNotExist(): void
    {
        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            ['id' => Uuid::randomHex()],
            'non_existent_entity_definition',
            []
        );

        static::assertNull($result);
    }

    public function testResetShouldClearRequiredFieldsCache(): void
    {
        $result1 = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            [
                'id' => Uuid::randomHex(),
                'profileName' => 'profile',
                'gatewayName' => 'gateway',
                'level' => 'error',
                'code' => 'some_code',
                'userFixable' => true,
            ],
            SwagMigrationLoggingDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result1);

        $this->validationService->reset();

        $result2 = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            [
                'id' => Uuid::randomHex(),
                'profileName' => 'profile',
                'gatewayName' => 'gateway',
                'level' => 'error',
                'code' => 'some_code',
                'userFixable' => true,
            ],
            SwagMigrationLoggingDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result2);

        $this->clearCacheData();

        static::assertCount(\count($result1->getLogs()), $result2->getLogs());
    }

    public function testValidNestedAssociationWithValidUuids(): void
    {
        $categoryId1 = Uuid::randomHex();
        $categoryId2 = Uuid::randomHex();

        $convertedData = [
            'id' => Uuid::randomHex(),
            'versionId' => Uuid::randomHex(),
            'stock' => 10,
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'Test Product',
                ],
            ],
            'categories' => [
                ['id' => $categoryId1],
                ['id' => $categoryId2],
            ],
        ];

        $result = $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            $convertedData,
            ProductDefinition::ENTITY_NAME,
            []
        );

        static::assertInstanceOf(MigrationValidationResult::class, $result);
        static::assertCount(0, $result->getLogs());
    }

    public function testValidationLogsAreSavedToDatabase(): void
    {
        $this->validationService->validate(
            $this->migrationContext,
            $this->context,
            [
                'id' => Uuid::randomHex(),
                'level' => 'error',
                'code' => 'some_code',
                'userFixable' => true,
            ],
            SwagMigrationLoggingDefinition::ENTITY_NAME,
            []
        );

        $this->clearCacheData();

        $logs = $this->loggingRepo->search(new Criteria(), $this->context)->getEntities();
        static::assertInstanceOf(SwagMigrationLoggingCollection::class, $logs);
        static::assertGreaterThan(0, $logs->count());
    }
}
