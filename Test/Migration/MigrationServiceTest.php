<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\EntitySearchResult;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MigrationServiceTest extends KernelTestCase
{
    /**
     * @var MigrationService
     */
    private $migrationService;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        /* @var Connection $connection */
        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->migrationService = self::$container->get(MigrationService::class);
        $this->migrationDataRepo = self::$container->get('swag_migration_data.repository');
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testMigrationApiGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            'shopware5.5',
            'product',
            'api',
            [
                'endpoint' => 'foo',
                'apiUser' => 'foo',
                'apiKey' => 'foo',
            ]
        );

        $this->migrationService->migrate($migrationContext);

        $criteria = new Criteria();
        /** @var EntitySearchResult $data */
        $data = $this->migrationDataRepo->search($criteria, $context);

        self::assertEquals(37, $data->getTotal());
    }

    public function testMigrationLocalGateway(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            'shopware5.5',
            'product',
            'local',
            [
                'dbName' => 'foo',
                'dbUser' => 'foo',
                'dbPassword' => 'foo',
            ]
        );

        $this->migrationService->migrate($migrationContext);

        $criteria = new Criteria();
        /** @var EntitySearchResult $data */
        $data = $this->migrationDataRepo->search($criteria, $context);

        self::assertEquals(37, $data->getTotal());
    }
}
