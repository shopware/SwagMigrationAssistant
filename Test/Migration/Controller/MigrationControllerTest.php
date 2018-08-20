<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Controller\MigrationController;
use SwagMigrationNext\Migration\AssetDownloadService;
use SwagMigrationNext\Migration\MigrationEnvironmentService;
use SwagMigrationNext\Migration\MigrationWriteService;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
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

        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $this->controller = new MigrationController(
            $this->getMigrationCollectService(
                self::$container->get('swag_migration_data.repository'),
                self::$container->get(Shopware55MappingService::class)
            ),
            self::$container->get(MigrationWriteService::class),
            self::$container->get(AssetDownloadService::class),
            self::$container->get(MigrationEnvironmentService::class),
            self::$container->get('swag_migration_profile.repository')
        );
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testFetchData(): void
    {
        $request = new Request([
            'profile' => 'shopware55',
            'gateway' => 'local',
            'entity' => ProductDefinition::getEntityName(),
            'credentialFields' => [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
        ]);
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $result = $this->controller->fetchData($request, $context);

        static::assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $content = json_decode($result->getContent(), true);

        static::assertTrue($content['success']);
    }

    public function testWriteData(): void
    {
        $request = new Request([
            'profile' => 'shopware55',
            'entity' => ProductDefinition::getEntityName(),
            'credentialFields' => [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
        ]);
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $result = $this->controller->writeData($request, $context);

        static::assertEquals(Response::HTTP_OK, $result->getStatusCode());
        $content = json_decode($result->getContent(), true);

        static::assertTrue($content['success']);
    }
}
