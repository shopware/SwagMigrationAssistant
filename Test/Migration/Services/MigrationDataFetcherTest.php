<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Converter\ConverterRegistry;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Logging\LogType;
use SwagMigrationNext\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\ProfileRegistry;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcher;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Profile\Shopware55\Converter\ProductConverter;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\MediaDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\OrderDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Shopware55ApiFactory;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Premapping\PaymentMethodReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\DataSet\InvalidCustomerDataSet;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;

class MigrationDataFetcherTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

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
            $this->loggingRepo
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
                    new DummyLoggingService()
                ),
            ])),

            new GatewayFactoryRegistry(new DummyCollection([
                new Shopware55ApiFactory(),
                new DummyLocalFactory(),
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
        $criteria->addFilter(new EqualsFilter('entity', MediaDefinition::getEntityName()));
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
        $criteria->addFilter(new EqualsFilter('entity', CategoryDefinition::getEntityName()));
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
        static::assertSame(5, $result->getTotal());
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
        $criteria->addFilter(new EqualsFilter('entity', CustomerDefinition::getEntityName()));
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
        $criteria->addFilter(new EqualsFilter('entity', ProductDefinition::getEntityName()));
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
        $criteria->addFilter(new EqualsFilter('entity', ProductDefinition::getEntityName()));
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
