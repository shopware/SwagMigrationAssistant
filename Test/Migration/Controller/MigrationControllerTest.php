<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Controller\MigrationController;
use SwagMigrationNext\Migration\AssetDownloadService;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\MigrationWriteService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MigrationControllerTest extends KernelTestCase
{
    use MigrationServicesTrait;

    /**
     * @var MigrationController
     */
    private $controller;

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

        $this->controller = new MigrationController(
            $this->getMigrationCollectService(
                self::$container->get('swag_migration_data.repository'),
                self::$container->get(MappingService::class)
            ),
            self::$container->get(MigrationWriteService::class),
            self::$container->get(AssetDownloadService::class)
        );
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testFetchData()
    {
        $request = new Request([
            'profile' => 'shopware55',
            'gateway' => 'local',
            'entity' => ProductDefinition::getEntityName(),
            'credentials' => [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
        ]);
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $result = $this->controller->fetchData($request, $context);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $content = json_decode($result->getContent(), true);

        $this->assertTrue($content['success']);
    }

    public function testWriteData()
    {
        $request = new Request([
            'profile' => 'shopware55',
            'entity' => ProductDefinition::getEntityName(),
            'credentials' => [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
        ]);
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $result = $this->controller->writeData($request, $context);

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $content = json_decode($result->getContent(), true);

        $this->assertTrue($content['success']);
    }
}
