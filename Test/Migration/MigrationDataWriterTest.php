<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\Currency\CurrencyEntity;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Data\SwagMigrationDataEntity;
use SwagMigrationNext\Migration\Logging\LogType;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Migration\Writer\CustomerWriter;
use SwagMigrationNext\Migration\Writer\ProductWriter;
use SwagMigrationNext\Migration\Writer\WriterRegistry;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyMediaFileService;
use SwagMigrationNext\Test\Mock\Migration\Logging\DummyLoggingService;

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
    private $discountRepo;

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

    protected function setUp(): void
    {
        $this->profileUuidService = new MigrationProfileUuidService(
            $this->getContainer()->get('swag_migration_profile.repository'),
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_TYPE
        );

        $this->runUuid = Uuid::uuid4()->getHex();
        $runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                ],
            ],
            Context::createDefaultContext()
        );

        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );
        $this->migrationDataWriter = $this->getContainer()->get(MigrationDataWriter::class);

        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->customerRepo = $this->getContainer()->get('customer.repository');
        $this->loggingService = new DummyLoggingService();
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

        $this->categoryRepo = $this->getContainer()->get('category.repository');
        $this->mediaRepo = $this->getContainer()->get('media.repository');
        $this->productTranslationRepo = $this->getContainer()->get('product_translation.repository');
        $this->discountRepo = $this->getContainer()->get('customer_group_discount.repository');
        $this->orderRepo = $this->getContainer()->get('order.repository');
        $this->currencyRepo = $this->getContainer()->get('currency.repository');
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
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $customerData = $this->migrationDataRepo->search($criteria, $context);

        /** @var $data SwagMigrationDataEntity */
        $data = $customerData->first();
        $customer = $data->jsonSerialize();
        $customer['id'] = $data->getId();
        unset($customer['run']);
        unset($customer['converted'][$missingProperty]);

        $this->migrationDataRepo->update([$customer], $context);
        $customerTotalBefore = $this->customerRepo->search($criteria, $context)->getTotal();
        $this->dummyDataWriter->writeData($migrationContext, $context);
        $customerTotalAfter = $this->customerRepo->search($criteria, $context)->getTotal();

        self::assertSame(0, $customerTotalAfter - $customerTotalBefore);
        self::assertCount(1, $this->loggingService->getLoggingArray());
        $this->loggingService->resetLogging();

        $failureConvertCriteria = new Criteria();
        $failureConvertCriteria->addFilter(new EqualsFilter('writeFailure', true));
        $result = $this->migrationDataRepo->search($failureConvertCriteria, $context);
        self::assertSame(3, $result->getTotal());
    }

    public function testWriteCustomerData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $customerTotalBefore = $this->customerRepo->search($criteria, $context)->getTotal();

        $this->migrationDataWriter->writeData($migrationContext, $context);
        $customerTotalAfter = $this->customerRepo->search($criteria, $context)->getTotal();

        self::assertSame(3, $customerTotalAfter - $customerTotalBefore);
    }

    public function testWriteOrderData(): void
    {
        $context = Context::createDefaultContext();

        // Add users, who have ordered
        $userMigrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );
        $this->migrationDataFetcher->fetchData($userMigrationContext, $context);
        $this->migrationDataWriter->writeData($userMigrationContext, $context);

        // Add orders
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            OrderDefinition::getEntityName(),
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
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
        $this->migrationDataWriter->writeData($migrationContext, $context);
        $orderTotalAfter = $this->orderRepo->search($criteria, $context)->getTotal();
        $currencyTotalAfter = $this->currencyRepo->search($criteria, $context)->getTotal();
        /** @var CurrencyEntity $usdResultAfter */
        $usdResultAfter = $this->currencyRepo->search($usdFactorCriteria, $context)->first();
        $jpyInvalidTotalAfter = $this->currencyRepo->search($jpyInvalidCriteria, $context)->getTotal();

        self::assertSame(2, $orderTotalAfter - $orderTotalBefore);
        self::assertSame(1, $currencyTotalAfter - $currencyTotalBefore);
        self::assertSame(0.2, $usdResultAfter->getFactor() - $usdResultBefore->getFactor());
        self::assertSame(0, $jpyInvalidTotalAfter - $jpyInvalidTotalBefore);
    }

    public function testWriteCustomerGroupDiscounts(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $discountTotalBefore = $this->discountRepo->search($criteria, $context)->getTotal();

        $this->migrationDataWriter->writeData($migrationContext, $context);
        $discountTotalAfter = $this->discountRepo->search($criteria, $context)->getTotal();

        self::assertSame(1, $discountTotalAfter - $discountTotalBefore);
    }

    public function testWriteAssetData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $totalBefore = $this->mediaRepo->search($criteria, $context)->getTotal();

        $this->migrationDataWriter->writeData($migrationContext, $context);
        $totalAfter = $this->mediaRepo->search($criteria, $context)->getTotal();

        self::assertSame(23, $totalAfter - $totalBefore);
    }

    public function testWriteCategoryData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $totalBefore = $this->categoryRepo->search($criteria, $context)->getTotal();

        $this->migrationDataWriter->writeData($migrationContext, $context);
        $totalAfter = $this->categoryRepo->search($criteria, $context)->getTotal();

        self::assertSame(8, $totalAfter - $totalBefore);
    }

    public function testWriteProductData(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();

        $this->migrationDataWriter->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        self::assertSame(14, $productTotalAfter - $productTotalBefore); //TODO change back to 42 after variant support is implemented
    }

    public function testWriteTranslationData(): void
    {
        $context = Context::createDefaultContext();

        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataWriter->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            'translation',
            [],
            0,
            250
        );
        $productTranslationTotalBefore = $this->getTranslationTotal();
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationDataWriter->writeData($migrationContext, $context);
        $productTranslationTotalAfter = $this->getTranslationTotal();

        self::assertSame(14, $productTotalAfter - $productTotalBefore); //TODO change back to 42 after variant support is implemented
        self::assertSame(0, $productTranslationTotalAfter - $productTranslationTotalBefore);  //TODO change back to 2 after translation support is implemented
    }

    public function testWriteDataWithUnknownWriter(): void
    {
        $context = Context::createDefaultContext();
        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [],
            0,
            250
        );
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->dummyDataWriter->writeData($migrationContext, $context);

        $logs = $this->loggingService->getLoggingArray();
        static::assertSame(LogType::WRITER_NOT_FOUND, $logs[0]['logEntry']['code']);
        static::assertCount(1, $logs);
    }

    private function getTranslationTotal(): int
    {
        return (int) $this->getContainer()->get(Connection::class)
            ->executeQuery('SELECT count(*) FROM product_translation')
            ->fetchColumn();
    }
}
