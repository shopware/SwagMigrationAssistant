<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MigrationCollectServiceTest extends KernelTestCase
{
    use MigrationServicesTrait;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var RepositoryInterface
     */
    private $productRepo;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        /* @var Connection $connection */
        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->migrationDataRepo = self::$container->get('swag_migration_data.repository');
        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->migrationDataRepo,
            self::$container->get(Shopware55MappingService::class)
        );
        $this->productRepo = self::$container->get('product.repository');
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testFetchAssetDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', Shopware55Profile::PROFILE_NAME));
        $criteria->addFilter(new TermQuery('entity', MediaDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertEquals(23, $result->getTotal());
    }

    public function testFetchCategoryDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', Shopware55Profile::PROFILE_NAME));
        $criteria->addFilter(new TermQuery('entity', CategoryDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertEquals(8, $result->getTotal());
    }

    public function testFetchTranslationDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            'translation',
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', Shopware55Profile::PROFILE_NAME));
        $criteria->addFilter(new TermQuery('entity', 'translation'));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertEquals(5, $result->getTotal());
    }

    public function testFetchCustomerDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', Shopware55Profile::PROFILE_NAME));
        $criteria->addFilter(new TermQuery('entity', CustomerDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertEquals(2, $result->getTotal());
    }

    public function testFetchProductDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
            0,
            250
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', Shopware55Profile::PROFILE_NAME));
        $criteria->addFilter(new TermQuery('entity', ProductDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertEquals(37, $result->getTotal());
    }

    public function testFetchProductDataLocalGateway(): void
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
        $criteria->addFilter(new TermQuery('profile', Shopware55Profile::PROFILE_NAME));
        $criteria->addFilter(new TermQuery('entity', ProductDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertEquals(37, $result->getTotal());
    }
}
