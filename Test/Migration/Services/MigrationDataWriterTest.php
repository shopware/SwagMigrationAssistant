<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\InvoicePayment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Data\SwagMigrationDataEntity;
use SwagMigrationNext\Migration\Logging\LogType;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Migration\Writer\CustomerWriter;
use SwagMigrationNext\Migration\Writer\ProductWriter;
use SwagMigrationNext\Migration\Writer\WriterRegistry;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\MediaDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\OrderDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Premapping\OrderStateReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\PaymentMethodReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\SalutationReader;
use SwagMigrationNext\Profile\Shopware55\Premapping\TransactionStateReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyMediaFileService;

class MigrationDataWriterTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $productTranslationRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepo;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationDataWriter;

    /**
     * @var string
     */
    private $runUuid;

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    /**
     * @var MigrationDataWriterInterface
     */
    private $dummyDataWriter;

    /**
     * @var DummyLoggingService
     */
    private $loggingService;

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepo;

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
    private $stateMachineStateRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $stateMachineRepository;

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
    private $profileRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepo;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var MappingService
     */
    private $mappingService;

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
        $this->loggingService = new DummyLoggingService();
        $this->mappingService = $this->getContainer()->get(MappingService::class);
        $this->migrationDataWriter = $this->getContainer()->get(MigrationDataWriter::class);
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->migrationDataRepo,
            $this->mappingService,
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );

        $this->dummyDataWriter = new MigrationDataWriter(
            $this->migrationDataRepo,
            new WriterRegistry(
                [
                    new ProductWriter($this->productRepo, new StructNormalizer()),
                    new CustomerWriter($this->customerRepo),
                ]
            ),
            new DummyMediaFileService(),
            $this->loggingService
        );
    }

    public function requiredProperties(): array
    {
        return [
            ['email'],
            ['firstName'],
            ['lastName'],
        ];
    }

    /**
     * @dataProvider requiredProperties
     */
    public function testWriteInvalidData(string $missingProperty): void
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
        $customerData = $this->migrationDataRepo->search($criteria, $context);

        /** @var SwagMigrationDataEntity $data */
        $data = $customerData->first();
        $customer = $data->jsonSerialize();
        $customer['id'] = $data->getId();
        unset($customer['run'], $customer['converted'][$missingProperty]);

        $this->migrationDataRepo->update([$customer], $context);
        $customerTotalBefore = $this->customerRepo->search($criteria, $context)->getTotal();
        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext) {
            $this->dummyDataWriter->writeData($migrationContext, $context);
        });
        $customerTotalAfter = $this->customerRepo->search($criteria, $context)->getTotal();

        static::assertSame(0, $customerTotalAfter - $customerTotalBefore);
        static::assertCount(1, $this->loggingService->getLoggingArray());
        $this->loggingService->resetLogging();

        $failureConvertCriteria = new Criteria();
        $failureConvertCriteria->addFilter(new EqualsFilter('writeFailure', true));
        $result = $this->migrationDataRepo->search($failureConvertCriteria, $context);
        static::assertSame(3, $result->getTotal());
    }

    public function testWriteCustomerData(): void
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
        $customerTotalBefore = $this->customerRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext) {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $customerTotalAfter = $this->customerRepo->search($criteria, $context)->getTotal();

        static::assertSame(3, $customerTotalAfter - $customerTotalBefore);
    }

    public function testWriteOrderData(): void
    {
        $context = Context::createDefaultContext();
        // Add users, who have ordered
        $userMigrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new CustomerDataSet(),
            0,
            250
        );
        $this->migrationDataFetcher->fetchData($userMigrationContext, $context);
        $context->scope(Context::USER_SCOPE, function (Context $context) use ($userMigrationContext) {
            $this->migrationDataWriter->writeData($userMigrationContext, $context);
        });

        // Add orders
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new OrderDataSet(),
            0,
            250
        );

        $criteria = new Criteria();
        $usdFactorCriteria = (new Criteria())->addFilter(new EqualsFilter('shortName', 'USD'));
        $jpyInvalidCriteria = (new Criteria())->addFilter(new EqualsFilter('symbol', '&yen;'));

        // Get data before writing
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $orderTotalBefore = $this->orderRepo->search($criteria, $context)->getTotal();
        $currencyTotalBefore = $this->currencyRepo->search($criteria, $context)->getTotal();
        /** @var CurrencyEntity $usdResultBefore */
        $usdResultBefore = $this->currencyRepo->search($usdFactorCriteria, $context)->first();
        $jpyInvalidTotalBefore = $this->currencyRepo->search($jpyInvalidCriteria, $context)->getTotal();

        // Get data after writing
        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext) {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $orderTotalAfter = $this->orderRepo->search($criteria, $context)->getTotal();
        $currencyTotalAfter = $this->currencyRepo->search($criteria, $context)->getTotal();
        /** @var CurrencyEntity $usdResultAfter */
        $usdResultAfter = $this->currencyRepo->search($usdFactorCriteria, $context)->first();
        $jpyInvalidTotalAfter = $this->currencyRepo->search($jpyInvalidCriteria, $context)->getTotal();

        static::assertSame(2, $orderTotalAfter - $orderTotalBefore);
        static::assertSame(1, $currencyTotalAfter - $currencyTotalBefore);
        static::assertSame(0.2, $usdResultAfter->getFactor() - $usdResultBefore->getFactor());
        static::assertSame(0, $jpyInvalidTotalAfter - $jpyInvalidTotalBefore);
    }

    public function testWriteMediaData(): void
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
        $totalBefore = $this->mediaRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext) {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $totalAfter = $this->mediaRepo->search($criteria, $context)->getTotal();

        static::assertSame(23, $totalAfter - $totalBefore);
    }

    public function testWriteCategoryData(): void
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
        $totalBefore = $this->categoryRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext) {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $totalAfter = $this->categoryRepo->search($criteria, $context)->getTotal();

        static::assertSame(8, $totalAfter - $totalBefore);
    }

    public function testWriteProductData(): void
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
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext) {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        static::assertSame(14, $productTotalAfter - $productTotalBefore); //TODO change back to 42 after variant support is implemented
    }

    public function testWriteTranslationData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new ProductDataSet(),
            0,
            250
        );
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataWriter->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        $migrationContext = new MigrationContext(
            $this->connection,
            $this->runUuid,
            new TranslationDataSet(),
            0,
            250
        );
        $productTranslationTotalBefore = $this->getTranslationTotal();
        $this->migrationDataFetcher->fetchData($migrationContext, $context);

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext) {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $productTranslationTotalAfter = $this->getTranslationTotal();

        static::assertSame(14, $productTotalAfter - $productTotalBefore); //TODO change back to 42 after variant support is implemented
        static::assertSame(0, $productTranslationTotalAfter - $productTranslationTotalBefore);  //TODO change back to 2 after translation support is implemented
    }

    public function testWriteDataWithUnknownWriter(): void
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
        $this->dummyDataWriter->writeData($migrationContext, $context);

        $logs = $this->loggingService->getLoggingArray();

        static::assertSame(LogType::WRITER_NOT_FOUND, $logs[0]['logEntry']['code']);
        static::assertCount(1, $logs);
    }

    private function initRepos(): void
    {
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->paymentRepo = $this->getContainer()->get('payment_method.repository');
        $this->mediaRepo = $this->getContainer()->get('media.repository');
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->categoryRepo = $this->getContainer()->get('category.repository');
        $this->orderRepo = $this->getContainer()->get('order.repository');
        $this->customerRepo = $this->getContainer()->get('customer.repository');
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->stateMachineRepository = $this->getContainer()->get('state_machine.repository');
        $this->stateMachineStateRepository = $this->getContainer()->get('state_machine_state.repository');
        $this->productTranslationRepo = $this->getContainer()->get('product_translation.repository');
        $this->currencyRepo = $this->getContainer()->get('currency.repository');
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
            $this->context
        );
    }

    private function initMapping(): void
    {
        $orderStateUuid = $this->getOrderStateUuid(
            $this->stateMachineRepository,
            $this->stateMachineStateRepository,
            0,
            $this->context
        );
        $this->mappingService->createNewUuid($this->connectionId, OrderStateReader::getMappingName(), '0', $this->context, [], $orderStateUuid);

        $transactionStateUuid = $this->getTransactionStateUuid(
            $this->stateMachineRepository,
            $this->stateMachineStateRepository,
            17,
            $this->context
        );
        $this->mappingService->createNewUuid($this->connectionId, TransactionStateReader::getMappingName(), '17', $this->context, [], $transactionStateUuid);

        $paymentUuid = $this->getPaymentUuid(
            $this->paymentRepo,
            InvoicePayment::class,
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

    private function getTranslationTotal(): int
    {
        return (int) $this->getContainer()->get(Connection::class)
            ->executeQuery('SELECT count(*) FROM product_translation')
            ->fetchColumn();
    }
}
