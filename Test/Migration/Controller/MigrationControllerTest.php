<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Controller\MigrationController;
use SwagMigrationNext\Migration\Asset\HttpAssetDownloadService;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentService;
use SwagMigrationNext\Migration\Service\MigrationWriteService;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
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
        $request = new Request([], [
            'profile' => Shopware55Profile::PROFILE_NAME,
            'runUuid' => $this->runUuid,
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

        static::assertSame(Response::HTTP_OK, $result->getStatusCode());
    }

    public function testWriteData(): void
    {
        $request = new Request([], [
            'runUuid' => $this->runUuid,
            'entity' => ProductDefinition::getEntityName(),
        ]);
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $result = $this->controller->writeData($request, $context);

        static::assertSame(Response::HTTP_OK, $result->getStatusCode());
    }
}
