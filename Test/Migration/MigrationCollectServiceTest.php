<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationCollectService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MigrationCollectServiceTest extends KernelTestCase
{
    /**
     * @var MigrationCollectService
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

        $this->migrationCollectService = self::$container->get(MigrationCollectService::class);
        $this->migrationDataRepo = self::$container->get('swag_migration_data.repository');
        $this->productRepo = self::$container->get('product.repository');
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testFetchDataLocalGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [
                'dbHost' => 'foo',
                'dbName' => 'foo',
                'dbUser' => 'foo',
                'dbPassword' => 'foo',
            ]
        );

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('profile', Shopware55Profile::PROFILE_NAME));
        $criteria->addFilter(new TermQuery('entityName', ProductDefinition::getEntityName()));
        /** @var EntitySearchResult $result */
        $result = $this->migrationDataRepo->search($criteria, $context);
        self::assertEquals(37, $result->getTotal());

        /** @var \Shopware\Core\Framework\Struct\ArrayStruct $migrationData */
        $migrationData = $result->first();
        $unmapped = $migrationData->get('unmapped');
        $converted = $migrationData->get('converted');

        self::assertArrayNotHasKey('name', $unmapped);
        self::assertNotNull($converted['name']);
    }
}
