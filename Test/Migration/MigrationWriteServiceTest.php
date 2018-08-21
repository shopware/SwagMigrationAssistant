<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use PDO;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationWriteService;
use SwagMigrationNext\Migration\MigrationWriteServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MigrationWriteServiceTest extends KernelTestCase
{
    use MigrationServicesTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var RepositoryInterface
     */
    private $productRepo;

    /**
     * @var RepositoryInterface
     */
    private $categoryRepo;

    /**
     * @var RepositoryInterface
     */
    private $mediaRepo;

    /**
     * @var RepositoryInterface
     */
    private $productTranslationRepo;

    /**
     * @var RepositoryInterface
     */
    private $customerRepo;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var MigrationWriteServiceInterface
     */
    private $migrationWriteService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        /* @var Connection $connection */
        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->migrationCollectService = $this->getMigrationCollectService(
            self::$container->get('swag_migration_data.repository'),
            self::$container->get(Shopware55MappingService::class)
        );
        $this->migrationWriteService = self::$container->get(MigrationWriteService::class);
        $this->productRepo = self::$container->get('product.repository');
        $this->categoryRepo = self::$container->get('category.repository');
        $this->mediaRepo = self::$container->get('media.repository');
        $this->productTranslationRepo = self::$container->get('product_translation.repository');
        $this->customerRepo = self::$container->get('customer.repository');
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testWriteCustomerData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250,
            null,
            Defaults::SALES_CHANNEL
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $productTotalBefore = $this->customerRepo->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->customerRepo->search($criteria, $context)->getTotal();

        /* @var EntitySearchResult $result */
        self::assertEquals(2, $productTotalAfter - $productTotalBefore);
    }

    public function testWriteAssetData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $totalBefore = $this->mediaRepo->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $totalAfter = $this->mediaRepo->search($criteria, $context)->getTotal();

        /* @var EntitySearchResult $result */
        self::assertEquals(23, $totalAfter - $totalBefore);
    }

    public function testWriteCategoryData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $totalBefore = $this->categoryRepo->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $totalAfter = $this->categoryRepo->search($criteria, $context)->getTotal();

        /* @var EntitySearchResult $result */
        self::assertEquals(8, $totalAfter - $totalBefore);
    }

    public function testWriteProductData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        /* @var EntitySearchResult $result */
        self::assertEquals(42, $productTotalAfter - $productTotalBefore);
    }

    public function testWriteTranslationData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();
        $this->migrationCollectService->fetchData($migrationContext, $context);
        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            'translation',
            [],
            0,
            250
        );
        $productTranslationTotalBefore = $this->getTranslationTotal();
        $this->migrationCollectService->fetchData($migrationContext, $context);
        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTranslationTotalAfter = $this->getTranslationTotal();

        /* @var EntitySearchResult $result */
        self::assertEquals(42, $productTotalAfter - $productTotalBefore);
        self::assertEquals(2, $productTranslationTotalAfter - $productTranslationTotalBefore);
    }

    public function testWriteProductDataWithNoData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );
        $this->migrationWriteService->writeData($migrationContext, $context);

        $criteria = new Criteria();
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        static::assertEquals(0, $productTotalAfter);
    }

    private function getTranslationTotal()
    {
        return $this->connection->query('SELECT count(*) FROM product_translation')->fetch(PDO::FETCH_COLUMN);
    }
}
