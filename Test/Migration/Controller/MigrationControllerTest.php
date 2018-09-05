<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Controller\MigrationController;
use SwagMigrationNext\Migration\HttpAssetDownloadService;
use SwagMigrationNext\Migration\MigrationEnvironmentService;
use SwagMigrationNext\Migration\MigrationWriteService;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MigrationControllerTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var MigrationController
     */
    private $controller;

    protected function setUp()
    {
        $this->controller = new MigrationController(
            $this->getMigrationCollectService(
                $this->getContainer()->get('swag_migration_data.repository'),
                $this->getContainer()->get(Shopware55MappingService::class)
            ),
            $this->getContainer()->get(MigrationWriteService::class),
            $this->getContainer()->get(HttpAssetDownloadService::class),
            $this->getContainer()->get(MigrationEnvironmentService::class),
            $this->getContainer()->get('swag_migration_profile.repository')
        );
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
    }
}
