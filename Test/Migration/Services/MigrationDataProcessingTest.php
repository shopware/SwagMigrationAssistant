<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\Salutation\SalutationCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingDefinition;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;
use SwagMigrationAssistant\Test\Mock\DataSet\InvalidCustomerDataSet;

/**
 * Combines tests for data fetching and converting
 */
#[Package('services-settings')]
class MigrationDataProcessingTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MigrationServicesTrait;

    private MigrationDataFetcherInterface $migrationDataFetcher;

    private MigrationDataConverterInterface $migrationDataConverter;

    /**
     * @var EntityRepository<SwagMigrationDataCollection>
     */
    private EntityRepository $migrationDataRepo;

    /**
     * @var EntityRepository<SalutationCollection>
     */
    private EntityRepository $salutationRepo;

    /**
     * @var EntityRepository<SwagMigrationLoggingCollection>
     */
    private EntityRepository $loggingRepo;

    private string $runUuid;

    private string $connectionId;

    private SwagMigrationConnectionEntity $connection;

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

    private Context $context;

    private MappingService $mappingService;

    /**
     * @var EntityRepository<ShippingMethodCollection>
     */
    private EntityRepository $shippingRepo;

    /**
     * @var EntityRepository<CountryCollection>
     */
    private EntityRepository $countryRepo;

    /**
     * @var EntityRepository<SalesChannelCollection>
     */
    private EntityRepository $salesChannelRepo;

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
        $this->mappingService = $this->createMappingService();
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->loggingRepo,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('language.repository'),
            static::getContainer()->get(ReaderRegistry::class)
        );
        $this->migrationDataConverter = $this->getMigrationDataConverter(
            static::getContainer()->get(EntityWriter::class),
            $this->mappingService,
            static::getContainer()->get(MediaFileService::class),
            $this->loggingRepo,
            static::getContainer()->get(SwagMigrationDataDefinition::class),
            $this->paymentRepo,
            $this->shippingRepo,
            $this->countryRepo,
            $this->salesChannelRepo
        );
    }

    public function initMapping(): void
    {
        $paymentUuid = $this->getPaymentUuid(
            $this->paymentRepo,
            'invoice',
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

        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '1', $this->context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');
        $this->mappingService->getOrCreateMapping($this->connectionId, DefaultEntities::CUSTOMER_GROUP, '2', $this->context, Uuid::randomHex(), [], 'cfbd5018d38d41d8adca10d94fc8bdd6');

        $this->mappingService->writeMapping($this->context);
    }

    public function testFetchMediaDataApiGateway(): void
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

        static::assertCount(23, $data);
        static::assertArrayHasKey('uri', $data[0]);
        static::assertArrayHasKey('_locale', $data[10]);
        static::assertSame('27', $data[22]['id']);
        static::assertSame('download', $data[1]['name']);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::MEDIA));
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(23, $result->getTotal());
    }

    public function testFetchCategoryDataApiGateway(): void
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

        static::assertCount(9, $data);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::CATEGORY));
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(9, $result->getTotal());
    }

    public function testFetchTranslationDataApiGateway(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new TranslationDataSet(),
            0,
            250
        );

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);

        static::assertCount(13, $data);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', 'translation'));
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(13, $result->getTotal());
    }

    public function testFetchCustomerDataApiGateway(): void
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

        static::assertCount(3, $data);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::CUSTOMER));
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(3, $result->getTotal());
    }

    public function testFetchProductDataApiGateway(): void
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

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        static::assertCount(37, $data);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::PRODUCT));
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(37, $result->getTotal());
    }

    public function testFetchProductDataLocalGateway(): void
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

        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        static::assertCount(37, $data);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', DefaultEntities::PRODUCT));
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(37, $result->getTotal());
    }

    public function testFetchInvalidCustomerData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            new Shopware55Profile(),
            $this->connection,
            $this->runUuid,
            new InvalidCustomerDataSet(),
            0,
            250
        );

        $this->clearCacheData();
        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataConverter->convert($data, $migrationContext, $context);
        $logs = $this->loggingRepo->search(new Criteria(), $context)->getEntities();

        static::assertCount(4, $data);

        $countValidLogging = 0;
        $countInvalidLogging = 0;

        foreach ($logs as $log) {
            $type = $log->getLevel();

            if (
                ($type === LogEntryInterface::LOG_LEVEL_INFO && $log->getCode() === 'SWAG_MIGRATION_CUSTOMER_ENTITY_FIELD_REASSIGNED')
                || ($type === LogEntryInterface::LOG_LEVEL_WARNING && $log->getCode() === 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER_ADDRESS')
                || ($type === LogEntryInterface::LOG_LEVEL_WARNING && $log->getCode() === 'SWAG_MIGRATION_EMPTY_NECESSARY_FIELD_CUSTOMER')
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
        $logs = $this->migrationDataRepo->search($failureConvertCriteria, $context);
        static::assertSame(2, $logs->getTotal());
    }

    private function createMappingService(): MappingService
    {
        return new MappingService(
            static::getContainer()->get('swag_migration_mapping.repository'),
            static::getContainer()->get('locale.repository'),
            static::getContainer()->get('language.repository'),
            $this->countryRepo,
            static::getContainer()->get('currency.repository'),
            static::getContainer()->get('tax.repository'),
            static::getContainer()->get('number_range.repository'),
            static::getContainer()->get('rule.repository'),
            static::getContainer()->get('media_thumbnail_size.repository'),
            static::getContainer()->get('media_default_folder.repository'),
            static::getContainer()->get('category.repository'),
            static::getContainer()->get('cms_page.repository'),
            static::getContainer()->get('delivery_time.repository'),
            static::getContainer()->get('document_type.repository'),
            static::getContainer()->get(EntityWriter::class),
            static::getContainer()->get(SwagMigrationMappingDefinition::class)
        );
    }

    private function initRepos(): void
    {
        $this->connectionRepo = static::getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = static::getContainer()->get('swag_migration_run.repository');
        $this->loggingRepo = static::getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataRepo = static::getContainer()->get('swag_migration_data.repository');
        $this->paymentRepo = static::getContainer()->get('payment_method.repository');
        $this->salutationRepo = static::getContainer()->get('salutation.repository');
        $this->shippingRepo = static::getContainer()->get('shipping_method.repository');
        $this->countryRepo = static::getContainer()->get('country.repository');
        $this->salesChannelRepo = static::getContainer()->get('sales_channel.repository');
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
                    'profileName' => Shopware55Profile::PROFILE_NAME,
                    'gatewayName' => ShopwareLocalGateway::GATEWAY_NAME,
                ],
            ],
            Context::createDefaultContext()
        );
    }
}
