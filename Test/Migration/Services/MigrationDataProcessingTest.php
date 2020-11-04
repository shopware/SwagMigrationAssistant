<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Migration\Services;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingEntity;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
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
class MigrationDataProcessingTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MigrationDataConverterInterface
     */
    private $migrationDataConverter;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepo;

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
    private $shippingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $countryRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepo;

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
            $this->getContainer()->get(SwagMigrationDataDefinition::class),
            $this->getContainer()->get(DataSetRegistry::class),
            $this->getContainer()->get('currency.repository'),
            $this->getContainer()->get('language.repository'),
            $this->getContainer()->get(ReaderRegistry::class)
        );
        $this->migrationDataConverter = $this->getMigrationDataConverter(
            $this->getContainer()->get(EntityWriter::class),
            $this->mappingService,
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo,
            $this->getContainer()->get(SwagMigrationDataDefinition::class),
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

        static::assertCount(12, $data);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $this->runUuid));
        $criteria->addFilter(new EqualsFilter('entity', 'translation'));
        $result = $this->migrationDataRepo->search($criteria, $context);
        static::assertSame(12, $result->getTotal());
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
        $result = $this->loggingRepo->search(new Criteria(), $context);

        static::assertCount(4, $data);

        $countValidLogging = 0;
        $countInvalidLogging = 0;

        /** @var SwagMigrationLoggingEntity $log */
        foreach ($result->getElements() as $log) {
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
        $result = $this->migrationDataRepo->search($failureConvertCriteria, $context);
        static::assertSame(2, $result->getTotal());
    }

    private function initRepos(): void
    {
        $this->connectionRepo = $this->getContainer()->get('swag_migration_connection.repository');
        $this->runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->paymentRepo = $this->getContainer()->get('payment_method.repository');
        $this->salutationRepo = $this->getContainer()->get('salutation.repository');
        $this->shippingRepo = $this->getContainer()->get('shipping_method.repository');
        $this->countryRepo = $this->getContainer()->get('country.repository');
        $this->salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
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
        $this->connection = $this->connectionRepo->search(new Criteria([$this->connectionId]), $this->context)->first();

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
