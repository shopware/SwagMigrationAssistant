<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Driver\Connection;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\GatewayFactoryRegistry;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Gateway\Shopware55\Api\Shopware55ApiFactory;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationEnvironmentService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;
use SwagMigrationNext\Test\Mock\Gateway\Dummy\Local\DummyLocalFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MigrationEnvironmentServiceTest extends KernelTestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var MigrationEnvironmentService
     */
    private $migrationEnvironmentService;

    protected function setUp()
    {
        parent::setUp();

        self::bootKernel();

        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        /** @var $gatewayFactoryRegistry GatewayFactoryRegistryInterface */
        $gatewayFactoryRegistry = new GatewayFactoryRegistry(new DummyCollection([
            new Shopware55ApiFactory(),
            new DummyLocalFactory(),
        ]));

        $this->migrationEnvironmentService = new MigrationEnvironmentService($gatewayFactoryRegistry);
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testGetEntityTotal(): void
    {
        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            CustomerDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertEquals(2, $total);

        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertEquals(37, $total);

        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            CategoryDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertEquals(8, $total);

        $migrationContext = new MigrationContext(
            Shopware55Profile::PROFILE_NAME,
            'local',
            MediaDefinition::getEntityName(),
            [],
            0,
            250
        );

        $total = $this->migrationEnvironmentService->getEntityTotal($migrationContext);

        self::assertEquals(23, $total);
    }
}
