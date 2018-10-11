<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationWriteService;
use SwagMigrationNext\Migration\Service\MigrationWriteServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;

class MigrationWriteServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

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
     * @var RepositoryInterface
     */
    private $discountRepo;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var MigrationWriteServiceInterface
     */
    private $migrationWriteService;

    /**
     * @var string
     */
    private $runUuid;

    protected function setUp()
    {
        $this->runUuid = Uuid::uuid4()->getHex();
        $runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profile' => Shopware55Profile::PROFILE_NAME,
                ],
            ],
            Context::createDefaultContext(Defaults::TENANT_ID)
        );

        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->getContainer()->get('swag_migration_data.repository'),
            $this->getContainer()->get(Shopware55MappingService::class)
        );
        $this->migrationWriteService = $this->getContainer()->get(MigrationWriteService::class);
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->categoryRepo = $this->getContainer()->get('category.repository');
        $this->mediaRepo = $this->getContainer()->get('media.repository');
        $this->productTranslationRepo = $this->getContainer()->get('product_translation.repository');
        $this->customerRepo = $this->getContainer()->get('customer.repository');
        $this->discountRepo = $this->getContainer()->get('customer_group_discount.repository');
    }

    public function testWriteCustomerData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
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
        $customerTotalBefore = $this->customerRepo->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $customerTotalAfter = $this->customerRepo->search($criteria, $context)->getTotal();

        self::assertSame(3, $customerTotalAfter - $customerTotalBefore);
    }

    public function testWriteCustomerGroupDiscounts(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
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
        $discountTotalBefore = $this->discountRepo->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $discountTotalAfter = $this->discountRepo->search($criteria, $context)->getTotal();

        self::assertSame(1, $discountTotalAfter - $discountTotalBefore);
    }

    public function testWriteAssetData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
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

        self::assertSame(23, $totalAfter - $totalBefore);
    }

    public function testWriteCategoryData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
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

        self::assertSame(8, $totalAfter - $totalBefore);
    }

    public function testWriteProductData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            $this->runUuid,
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

        self::assertSame(14, $productTotalAfter - $productTotalBefore); //TODO change back to 42 after variant support is implemented
    }

    public function testWriteTranslationData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $migrationContext = new MigrationContext(
            $this->runUuid,
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
            $this->runUuid,
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

        self::assertSame(14, $productTotalAfter - $productTotalBefore); //TODO change back to 42 after variant support is implemented
        self::assertSame(0, $productTranslationTotalAfter - $productTranslationTotalBefore);  //TODO change back to 2 after translation support is implemented
    }

    public function testWriteProductDataWithNoData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Uuid::uuid4()->getHex(),
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

        static::assertSame(0, $productTotalAfter);
    }

    private function getTranslationTotal(): int
    {
        return (int) $this->getContainer()->get(Connection::class)
            ->executeQuery('SELECT count(*) FROM product_translation')
            ->fetchColumn();
    }
}
