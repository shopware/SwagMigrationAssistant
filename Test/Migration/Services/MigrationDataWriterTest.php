<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\InvoicePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\TrackingEventClient;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\DeliveryTime\DeliveryTimeCollection;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Shopware\Core\System\StateMachine\StateMachineCollection;
use Shopware\Storefront\Theme\ThemeCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriter;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationAssistant\Migration\Service\SwagMigrationAccessTokenService;
use SwagMigrationAssistant\Migration\Writer\CustomerWriter;
use SwagMigrationAssistant\Migration\Writer\ProductWriter;
use SwagMigrationAssistant\Migration\Writer\WriterRegistry;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\DeliveryTimeReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\TransactionStateReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\DummyThemeService;
use SwagMigrationAssistant\Test\Mock\Migration\Logging\DummyLoggingService;
use SwagMigrationAssistant\Test\Mock\Migration\Media\DummyMediaFileService;
use SwagMigrationAssistant\Test\Mock\Migration\Service\DummyMigrationDataFetcher;

#[Package('services-settings')]
class MigrationDataWriterTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationServicesTrait;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $productRepo;

    /**
     * @var EntityRepository<CategoryCollection>
     */
    private EntityRepository $categoryRepo;

    /**
     * @var EntityRepository<SwagMigrationMediaFileCollection>
     */
    private EntityRepository $mediaRepo;

    /**
     * @var EntityRepository<CustomerCollection>
     */
    private EntityRepository $customerRepo;

    /**
     * @var EntityRepository<OrderCollection>
     */
    private EntityRepository $orderRepo;

    /**
     * @var EntityRepository<CurrencyCollection>
     */
    private EntityRepository $currencyRepo;

    private MigrationDataFetcherInterface $migrationDataFetcher;

    private MigrationDataConverterInterface $migrationDataConverter;

    private MigrationDataWriterInterface $migrationDataWriter;

    private string $runUuid;

    private MigrationDataWriterInterface $dummyDataWriter;

    private DummyLoggingService $loggingService;

    /**
     * @var EntityRepository<SwagMigrationLoggingCollection>
     */
    private EntityRepository $loggingRepo;

    /**
     * @var EntityRepository<SwagMigrationDataCollection>
     */
    private EntityRepository $migrationDataRepo;

    /**
     * @var EntityRepository<SwagMigrationMappingCollection>
     */
    private EntityRepository $migrationMappingRepo;

    private string $connectionId;

    private SwagMigrationConnectionEntity $connection;

    /**
     * @var EntityRepository<StateMachineStateCollection>
     */
    private EntityRepository $stateMachineStateRepository;

    /**
     * @var EntityRepository<StateMachineCollection>
     */
    private EntityRepository $stateMachineRepository;

    /**
     * @var EntityRepository<SwagMigrationConnectionCollection>
     */
    private EntityRepository $connectionRepo;

    /**
     * @var EntityRepository<SwagMigrationRunCollection>
     */
    private EntityRepository $runRepo;

    /**
     * @var EntityRepository<PaymentMethodCollection>
     */
    private EntityRepository $paymentRepo;

    /**
     * @var EntityRepository<SalutationCollection>
     */
    private EntityRepository $salutationRepo;

    private Context $context;

    private MappingService $mappingService;

    private EntityWriter $entityWriter;

    private Connection $dbConnection;

    /**
     * @var EntityRepository<DeliveryTimeCollection>
     */
    private EntityRepository $deliveryTimeRepo;

    /**
     * @var EntityRepository<LocaleCollection>
     */
    private EntityRepository $localeRepo;

    /**
     * @var EntityRepository<LanguageCollection>
     */
    private EntityRepository $languageRepo;

    /**
     * @var EntityRepository<SalesChannelCollection>
     */
    private EntityRepository $salesChannelRepo;

    /**
     * @var EntityRepository<ShippingMethodCollection>
     */
    private EntityRepository $shippingRepo;

    /**
     * @var EntityRepository<CountryCollection>
     */
    private EntityRepository $countryRepo;

    private RunService $runService;

    /**
     * @var EntityRepository<EntityCollection<Entity>>
     */
    private EntityRepository $themeSalesChannelRepo;

    /**
     * @var EntityRepository<ThemeCollection>
     */
    private EntityRepository $themeRepo;

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
        $this->mappingService = $this->createMappingService();
        $this->migrationDataWriter = static::getContainer()->get(MigrationDataWriter::class);
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->loggingRepo,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository'),
            static::getContainer()->get(ReaderRegistry::class)
        );
        $this->migrationDataConverter = $this->getMigrationDataConverter(
            $this->entityWriter,
            $this->mappingService,
            static::getContainer()->get(MediaFileService::class),
            $this->loggingRepo,
            static::getContainer()->get(SwagMigrationDataDefinition::class),
            $this->paymentRepo,
            $this->shippingRepo,
            $this->countryRepo,
            $this->salesChannelRepo
        );

        $mappingRepo = static::getContainer()->get('swag_migration_mapping.repository');

        $this->dummyDataWriter = new MigrationDataWriter(
            $this->entityWriter,
            $this->migrationDataRepo,
            new WriterRegistry(
                [
                    new ProductWriter($this->entityWriter, static::getContainer()->get(ProductDefinition::class)),
                    new CustomerWriter($this->entityWriter, static::getContainer()->get(CustomerDefinition::class)),
                ]
            ),
            new DummyMediaFileService(),
            $this->loggingService,
            static::getContainer()->get(SwagMigrationDataDefinition::class),
            $mappingRepo
        );

        $this->runService = new RunService(
            $this->runRepo,
            $this->connectionRepo,
            new DummyMigrationDataFetcher(new GatewayRegistry([]), $this->loggingService),
            new SwagMigrationAccessTokenService($this->runRepo),
            new DataSelectionRegistry([]),
            $this->migrationDataRepo,
            $this->mediaRepo,
            $this->salesChannelRepo,
            $this->themeRepo,
            new EntityIndexerRegistry([], static::getContainer()->get('messenger.bus.shopware'), static::getContainer()->get('event_dispatcher')),
            new DummyThemeService($this->themeSalesChannelRepo),
            $this->mappingService,
            static::getContainer()->get('cache.object'),
            new SwagMigrationDataDefinition(),
            $this->dbConnection,
            new LoggingService($this->loggingRepo),
            static::getContainer()->get(TrackingEventClient::class),
            static::getContainer()->get('messenger.bus.shopware')
        );
    }

    /**
     * @return list<array{0: string, 1: bool}>
     */
    public static function requiredProperties(): array
    {
        return [
            ['email', true],
            ['email', false],
            ['firstName', true],
            ['firstName', false],
            ['lastName', true],
            ['lastName', false],
        ];
    }

    public function testHandleWriteException(): void
    {
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new ProductDataSet(),
            0,
            250
        );

        $updateWrittenData = [];
        $this->invokeMethod($this->migrationDataWriter, 'handleWriteException', [
            new WriteException(),
            [
                'randomId' => [
                    'name' => 'myProduct',
                ],
            ],
            'product',
            &$updateWrittenData,
            $migrationContext,
            $this->context,
        ]);

        $loggingServiceProperty = (new \ReflectionClass(MigrationDataWriter::class))->getProperty('loggingService');
        $loggingServiceProperty->setAccessible(true);
        $loggingService = $loggingServiceProperty->getValue($this->migrationDataWriter);
        static::assertInstanceOf(LoggingServiceInterface::class, $loggingService);
        $loggingService->saveLogging($this->context);

        $log = $this->loggingRepo->search(new Criteria(), $this->context)->getEntities()->first();
        static::assertNotNull($log);
        static::assertSame('SWAG_MIGRATION_RUN_EXCEPTION', $log->getCode());
    }

    /**
     * @dataProvider requiredProperties
     */
    public function testWriteInvalidData(string $missingProperty, bool $mappingExists): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new CustomerDataSet(),
            0,
            250
        );

        $dataArray = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($dataArray, $migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', 'customer'));

        $data = $this->migrationDataRepo->search($criteria, $context)->getEntities()->first();
        static::assertNotNull($data);
        $customer = $data->jsonSerialize();
        $customer['id'] = $data->getId();
        unset($customer['run'], $customer['converted'][$missingProperty], $customer['autoIncrement']);

        $this->migrationDataRepo->update([$customer], $context);
        if (!$mappingExists) {
            $this->migrationMappingRepo->delete([['id' => $data->getMappingUuid()]], $context);
        }

        $customerTotalBefore = $this->customerRepo->search(new Criteria(), $context)->getTotal();
        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->dummyDataWriter->writeData($migrationContext, $context);
        });
        $customerTotalAfter = $this->dbConnection->executeQuery('select count(*) from customer')->fetchOne();

        static::assertSame(2, $customerTotalAfter - $customerTotalBefore);
        static::assertCount(1, $this->loggingService->getLoggingArray());
        $this->loggingService->resetLogging();

        $failureConvertCriteria = new Criteria([$data->getId()]);
        $failureConvertCriteria->addFilter(new EqualsFilter('writeFailure', true));
        $result = $this->migrationDataRepo->searchIds($failureConvertCriteria, $context)->firstId();
        static::assertNotNull($result);

        $checksumResetCriteria = new Criteria([$data->getMappingUuid() ?? '']);
        $result = $this->migrationMappingRepo->search($checksumResetCriteria, $context)->getEntities()->first();
        if ($mappingExists) {
            static::assertNotNull($result);
            static::assertNull($result->getChecksum());
        } else {
            static::assertNull($result);
        }
    }

    public function testWriteSalesChannelData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new SalesChannelDataSet(),
            0,
            250
        );

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $criteria = new Criteria();
        $salesChannelTotalBefore = $this->salesChannelRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $salesChannelTotalAfter = $this->dbConnection->executeQuery('select count(*) from sales_channel')->fetchOne();

        $this->runService->finishMigration($this->runUuid, $context);

        static::assertSame(2, $salesChannelTotalAfter - $salesChannelTotalBefore);
    }

    public function testAssignThemeToSalesChannel(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new SalesChannelDataSet(),
            0,
            250
        );

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $this->runService->finishMigration($this->runUuid, $context);

        $beforeThemeSalesChannel = $this->dbConnection->executeQuery('select count(*) from theme_sales_channel')->fetchOne();
        $this->runService->assignThemeToSalesChannel($this->runUuid, $context);
        $afterThemeSalesChannel = $this->dbConnection->executeQuery('select count(*) from theme_sales_channel')->fetchOne();

        static::assertSame(2, $afterThemeSalesChannel - $beforeThemeSalesChannel);
    }

    public function testWriteCustomerData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new CustomerDataSet(),
            0,
            250
        );

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $criteria = new Criteria();
        $customerTotalBefore = $this->customerRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $customerTotalAfter = $this->dbConnection->executeQuery('select count(*) from customer')->fetchOne();

        static::assertSame(3, $customerTotalAfter - $customerTotalBefore);
    }

    public function testWriteOrderData(): void
    {
        $context = Context::createDefaultContext();
        // Add users, who have ordered
        $userMigrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new CustomerDataSet(),
            0,
            250
        );
        $data = $this->migrationDataFetcher->fetchData($userMigrationContext, $context);
        $this->migrationDataConverter->convert($data, $userMigrationContext, $context);
        $this->clearCacheData();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($userMigrationContext): void {
            $this->migrationDataWriter->writeData($userMigrationContext, $context);
            $this->clearCacheData();
        });

        // Add orders
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new OrderDataSet(),
            0,
            250
        );

        $criteria = new Criteria();

        // Get data before writing
        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $this->clearCacheData();

        $orderTotalBefore = $this->orderRepo->search($criteria, $context)->getTotal();
        // Get data after writing
        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->migrationDataWriter->writeData($migrationContext, $context);
            $this->clearCacheData();
        });
        $orderTotalAfter = $this->orderRepo->search($criteria, $context)->getTotal();

        static::assertSame(2, $orderTotalAfter - $orderTotalBefore);
    }

    public function testWriteMediaData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new MediaDataSet(),
            0,
            250
        );

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $criteria = new Criteria();
        $totalBefore = $this->mediaRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $totalAfter = $this->dbConnection->executeQuery('select count(*) from media')->fetchOne();

        static::assertSame(23, $totalAfter - $totalBefore);
    }

    public function testWriteCategoryData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new CategoryDataSet(),
            0,
            250
        );

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $criteria = new Criteria();
        $totalBefore = $this->categoryRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $totalAfter = $this->dbConnection->executeQuery('select count(*) from category')->fetchOne();

        static::assertSame(9, $totalAfter - $totalBefore);
    }

    public function testWriteProductData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new ProductDataSet(),
            0,
            250
        );

        $this->clearCacheData();
        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);

        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();

        $context->scope(Context::USER_SCOPE, function (Context $context) use ($migrationContext): void {
            $this->migrationDataWriter->writeData($migrationContext, $context);
        });
        $productTotalAfter = (int) $this->dbConnection->executeQuery('select count(*) from product')->fetchOne();

        static::assertSame(42, $productTotalAfter - $productTotalBefore);
    }

    public function testWriteDataWithUnknownWriter(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new MediaDataSet(),
            0,
            250
        );
        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $this->dummyDataWriter->writeData($migrationContext, $context);

        $logs = $this->loggingService->getLoggingArray();

        static::assertSame('SWAG_MIGRATION_RUN_EXCEPTION', $logs[0]['code']);
        static::assertSame('SWAG_MIGRATION__WRITER_NOT_FOUND', $logs[0]['parameters']['exceptionCode']);
        static::assertCount(1, $logs);
    }

    private function createMappingService(): MappingService
    {
        return new MappingService(
            $this->migrationMappingRepo,
            $this->localeRepo,
            $this->languageRepo,
            $this->countryRepo,
            $this->currencyRepo,
            static::getContainer()->get('tax.repository'),
            static::getContainer()->get('number_range.repository'),
            static::getContainer()->get('rule.repository'),
            static::getContainer()->get('media_thumbnail_size.repository'),
            static::getContainer()->get('media_default_folder.repository'),
            $this->categoryRepo,
            static::getContainer()->get('cms_page.repository'),
            $this->deliveryTimeRepo,
            static::getContainer()->get('document_type.repository'),
            $this->entityWriter,
            static::getContainer()->get(SwagMigrationMappingDefinition::class)
        );
    }

    /**
     * @param array<mixed> $parameters
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = []): ?object
    {
        $method = (new \ReflectionClass($object::class))->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    private function initRepos(): void
    {
        $this->dbConnection = static::getContainer()->get(Connection::class);
        $this->entityWriter = static::getContainer()->get(EntityWriter::class);
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->paymentRepo = static::getContainer()->get('payment_method.repository');
        $this->mediaRepo = static::getContainer()->get('media.repository');
        $this->productRepo = static::getContainer()->get('product.repository');
        $this->categoryRepo = static::getContainer()->get('category.repository');
        $this->orderRepo = static::getContainer()->get('order.repository');
        $this->customerRepo = static::getContainer()->get('customer.repository');
        $this->connectionRepo = static::getContainer()->get('swag_migration_connection.repository');
        $this->migrationDataRepo = static::getContainer()->get('swag_migration_data.repository');
        $this->migrationMappingRepo = static::getContainer()->get('swag_migration_mapping.repository');
        $this->loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $this->stateMachineRepository = static::getContainer()->get('state_machine.repository');
        $this->stateMachineStateRepository = static::getContainer()->get('state_machine_state.repository');
        $this->currencyRepo = static::getContainer()->get('currency.repository');
        $this->salutationRepo = static::getContainer()->get('salutation.repository');
        $this->deliveryTimeRepo = static::getContainer()->get('delivery_time.repository');
        $this->localeRepo = static::getContainer()->get('locale.repository');
        $this->languageRepo = static::getContainer()->get('language.repository');
        $this->salesChannelRepo = static::getContainer()->get('sales_channel.repository');
        $this->themeRepo = static::getContainer()->get('theme.repository');
        $this->shippingRepo = static::getContainer()->get('shipping_method.repository');
        $this->countryRepo = static::getContainer()->get('country.repository');
        $this->themeSalesChannelRepo = static::getContainer()->get('theme_sales_channel.repository');
    }

    private function initConnectionAndRun(): void
    {
        $this->connectionId = Uuid::randomHex();
        $this->runUuid = Uuid::randomHex();

        $this->context->scope(MigrationContext::SOURCE_CONTEXT, function (Context $context): void {
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
                        'profileName' => Shopware55Profile::PROFILE_NAME,
                        'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                    ],
                ],
                $context
            );
        });
        $connection = $this->connectionRepo->search(new Criteria([$this->connectionId]), $this->context)->first();

        static::assertInstanceOf(SwagMigrationConnectionEntity::class, $connection);

        $this->connection = $connection;

        $this->runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'connectionId' => $this->connectionId,
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
        $this->mappingService->createMapping($this->connectionId, OrderStateReader::getMappingName(), '0', Uuid::randomHex(), [], $orderStateUuid);

        $transactionStateUuid = $this->getTransactionStateUuid(
            $this->stateMachineRepository,
            $this->stateMachineStateRepository,
            17,
            $this->context
        );
        $this->mappingService->getOrCreateMapping($this->connectionId, TransactionStateReader::getMappingName(), '17', $this->context, Uuid::randomHex(), [], $transactionStateUuid);

        $paymentUuid = $this->getPaymentUuid(
            $this->paymentRepo,
            InvoicePayment::class,
            $this->context
        );

        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '3', $this->context, Uuid::randomHex(), [], $paymentUuid);
        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '4', $this->context, Uuid::randomHex(), [], $paymentUuid);
        $this->mappingService->getOrCreateMapping($this->connectionId, PaymentMethodReader::getMappingName(), '5', $this->context, Uuid::randomHex(), [], $paymentUuid);

        $salutationUuid = $this->getSalutationUuid(
            $this->salutationRepo,
            'mr',
            $this->context
        );

        $this->mappingService->getOrCreateMapping($this->connectionId, SalutationReader::getMappingName(), 'mr', $this->context, Uuid::randomHex(), [], $salutationUuid);
        $this->mappingService->getOrCreateMapping($this->connectionId, SalutationReader::getMappingName(), 'ms', $this->context, Uuid::randomHex(), [], $salutationUuid);

        $deliveryTimeUuid = $this->getFirstDeliveryTimeUuid($this->deliveryTimeRepo, $this->context);
        $this->mappingService->getOrCreateMapping($this->connectionId, DeliveryTimeReader::getMappingName(), DeliveryTimeReader::SOURCE_ID, $this->context, null, [], $deliveryTimeUuid);

        $currencyUuid = $this->getCurrencyUuid(
            $this->currencyRepo,
            'EUR',
            $this->context
        );
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CURRENCY, 'JPY', $this->context, Uuid::randomHex(), [], $currencyUuid);

        $currencyUuid = $this->getCurrencyUuid(
            $this->currencyRepo,
            'EUR',
            $this->context
        );
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CURRENCY, 'JPY', $this->context, Uuid::randomHex(), [], $currencyUuid);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CURRENCY, 'EUR', $this->context, Uuid::randomHex(), [], $currencyUuid);

        $languageUuid = $this->getLanguageUuid(
            $this->localeRepo,
            $this->languageRepo,
            'de-DE',
            $this->context
        );
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-US', $this->context, Uuid::randomHex(), [], $languageUuid);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'en-GB', $this->context, Uuid::randomHex(), [], Defaults::LANGUAGE_SYSTEM);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'nl-NL', $this->context, Uuid::randomHex(), [], $languageUuid);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::LANGUAGE, 'bn-IN', $this->context, Uuid::randomHex(), [], $languageUuid);

        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $this->context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $this->context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');

        $categoryUuid = $this->getCategoryUuid($this->categoryRepo, $this->context);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CATEGORY, '3', $this->context, Uuid::randomHex(), [], $categoryUuid);
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CATEGORY, '39', $this->context, Uuid::randomHex(), [], $categoryUuid);

        $this->mappingService->writeMapping($this->context);
        $this->clearCacheData();
    }
}
