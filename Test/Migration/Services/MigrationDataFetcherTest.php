<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistry;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\LogType;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\ProfileRegistry;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcher;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\DataSet\InvalidCustomerDataSet;
use SwagMigrationAssistant\Test\Mock\DummyCollection;
use SwagMigrationAssistant\Test\Mock\Gateway\Dummy\Local\DummyLocalGateway;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;

class MigrationDataFetcherTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $dummyDataFetcher;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var EntityRepositoryInterface
     */
    private $connectionRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $runRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepo;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var MappingService
     */
    private $mappingService;

    /**
     * @var EntityRepositoryInterface
     */
    private $profileRepo;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->initRepos();
        $this->initConnectionAndRun();
        $this->initServices();
        $this->initMapping();
    }

    public function initServices(): void
    {
        $this->mappingService = $this->getContainer()->get(MappingService::class);
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get(EntityWriter::class),
            $this->mappingService,
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo,
            $this->getContainer()->get(SwagMigrationDataDefinition::class)
        );

        $this->loggingService = new DummyLoggingService();
        $this->dummyDataFetcher = new MigrationDataFetcher(
            new ProfileRegistry(new DummyCollection([
                new Shopware55Profile(
                    $this->getContainer()->get(EntityWriter::class),
                    new ConverterRegistry([
                        $this->getContainer()->get(ProductConverter::class),
                    ]),
                    $this->getContainer()->get(MediaFileService::class),
                    new DummyLoggingService(),
                    $this->getContainer()->get(SwagMigrationDataDefinition::class)
                ),
            ])),

            new GatewayRegistry(new DummyCollection([
                new Shopware55ApiGateway(),
                new DummyLocalGateway(),
            ])),

            $this->loggingService
        );
    }

    public function initMapping(): void
    {
        $paymentUuid = $this->getPaymentUuid(
            $this->paymentRepo,
            'invoice',
            $this->context
        );

        $this->mappingService->createNewUuid($this->connectionId, PaymentMethodReader::getMappingName(), '3', $this->context, [], $paymentUuid);
        $this->mappingService->createNewUuid($this->connectionId, PaymentMethodReader::getMappingName(), '4', $this->context, [], $paymentUuid);
        $this->mappingService->createNewUuid($this->connectionId, PaymentMethodReader::getMappingName(), '5', $this->context, [], $paymentUuid);

        $salutationUuid = $this->getSalutationUuid(
            $this->salutationRepo,
            'mr',
            $this->context
        );

        $this->mappingService->createNewUuid($this->connectionId, SalutationReader::getMappingName(), 'mr', $this->context, [], $salutationUuid);
        $this->mappingService->createNewUuid($this->connectionId, SalutationReader::getMappingName(), 'ms', $this->context, [], $salutationUuid);

        $this->mappingService->createNewUuid($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $this->context, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $this->mappingService->createNewUuid($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $this->context, [], 'cfbd5018d38d41d8adca10d94fc8bdd6');

        $this->mappingService->writeMapping($this->context);
    }

    public function testFetchMediaDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new MediaDataSet(),
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::MEDIA));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(23, $result->getTotal());
    }

    public function testFetchCategoryDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new CategoryDataSet(),
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::CATEGORY));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(8, $result->getTotal());
    }

    public function testFetchTranslationDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new TranslationDataSet(),
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', 'translation'));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(9, $result->getTotal());
    }

    public function testFetchCustomerDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new CustomerDataSet(),
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::CUSTOMER));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(3, $result->getTotal());
    }

    public function testFetchProductDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new ProductDataSet(),
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::PRODUCT));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(37, $result->getTotal());
    }

    public function testFetchProductDataLocalGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new ProductDataSet(),
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::PRODUCT));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(37, $result->getTotal());
    }

    public function testFetchInvalidCustomerData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new InvalidCustomerDataSet(),
            0,
            250
        );

        $this->clearCacheBefore();
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $result = $this->loggingRepo->search(new Criteria(), $context);

        static::assertSame(5, $result->getTotal());

        $countValidLogging = 0;
        $countInvalidLogging = 0;

        /** @var SwagMigrationLoggingEntity $log */
        foreach ($result->getElements() as $log) {
            $type = $log->getType();
            $logEntry = $log->getLogEntry();

            if (
                ($type === LoggingService::INFO_TYPE && $logEntry['title'] === 'Empty necessary data fields for address')
                || ($type === LoggingService::WARNING_TYPE && $logEntry['title'] === 'Empty necessary data fields')
                || ($type === LoggingService::INFO_TYPE && $logEntry['title'] === 'No default shipping address')
                || ($type === LoggingService::INFO_TYPE && $logEntry['title'] === 'No default billing and shipping address')
                || ($type === LoggingService::WARNING_TYPE && $logEntry['title'] === 'No address data')
            ) {
                ++$countValidLogging;
                continue;
            }

            ++$countInvalidLogging;
        }

        static::assertSame(5, $countValidLogging);
        static::assertSame(0, $countInvalidLogging);

        $failureConvertCriteria = new Criteria();
        $failureConvertCriteria->addFilter(new EqualsFilter('convertFailure', true));
        $result = $this->migrationDataRepo->search($failureConvertCriteria, $context);
        static::assertSame(2, $result->getTotal());
    }

    public function testFetchWithUnknownConverter(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new OrderDataSet(),
            0,
            250
        );

        $this->dummyDataFetcher->fetchData($migrationContext, $context);
        $logs = $this->loggingService->getLoggingArray();
        static::assertCount(1, $logs);
        static::assertSame(LogType::CONVERTER_NOT_FOUND, $logs[0]['logEntry']['code']);
    }

    private function initRepos(): void
    {
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->paymentRepo = $this->getContainer()->get('payment_method.repository');
        $this->profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->salutationRepo = $this->getContainer()->get('salutation.repository');
    }

    private function initConnectionAndRun(): void
    {
        $this->connectionId = Uuid::randomHex();
        $this->runUuid = Uuid::randomHex();

        $this->profileUuidService = new MigrationProfileUuidService(
            $this->profileRepo,
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_NAME
        );

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context) {
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
                        'profileId' => $this->profileUuidService->getProfileUuid(),
                    ],
                ],
                $context
            );
        });
        $this->connection = $this->connectionRepo->search(new Criteria([$this->connectionId]), $this->context)->first();

        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                ],
            ],
            Context::createDefaultContext()
        );
    }
}
