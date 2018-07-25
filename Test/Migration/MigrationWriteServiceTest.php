<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationWriteService;
use SwagMigrationNext\Migration\MigrationWriteServiceInterface;
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
            self::$container->get(MappingService::class)
        );
        $this->migrationWriteService = self::$container->get(MigrationWriteService::class);
        $this->productRepo = self::$container->get('product.repository');
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testWriteData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            []
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();

        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        /* @var EntitySearchResult $result */
        self::assertEquals(37, $productTotalAfter - $productTotalBefore);
    }

    public function testWriteDataWithNoData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            []
        );
        $this->migrationWriteService->writeData($migrationContext, $context);

        $criteria = new Criteria();
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        static::assertEquals(0, $productTotalAfter);
    }
}
