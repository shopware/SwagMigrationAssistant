<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
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
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;

class MigrationCollectServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var RepositoryInterface
     */
    private $productRepo;

    protected function setUp()
    {
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class)
        );
        $this->productRepo = $this->getContainer()->get('product.repository');
    }

    public function testFetchAssetDataApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            '',
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
            '',
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
            '',
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
            '',
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
            '',
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
            '',
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
