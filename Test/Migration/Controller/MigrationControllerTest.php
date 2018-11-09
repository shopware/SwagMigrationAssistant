<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Controller\MigrationController;
use SwagMigrationNext\Migration\Asset\HttpAssetDownloadService;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentService;
use SwagMigrationNext\Migration\Service\MigrationProgressService;
use SwagMigrationNext\Migration\Service\MigrationWriteService;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
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

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    protected function setUp()
    {
        $this->profileUuidService = new MigrationProfileUuidService($this->getContainer()->get('swag_migration_profile.repository'));
        $this->runUuid = Uuid::uuid4()->getHex();
        $runRepo = $this->getContainer()->get('swag_migration_run.repository');
        $runRepo->create(
            [
                [
                    'id' => $this->runUuid,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                ],
            ],
            Context::createDefaultContext(Defaults::TENANT_ID)
        );

        $this->controller = new MigrationController(
            $this->getMigrationCollectService(
                $this->getContainer()->get('swag_migration_data.repository'),
                $this->getContainer()->get(Shopware55MappingService::class),
                $this->getContainer()->get(MediaFileService::class),
                $this->getContainer()->get('swag_migration_logging.repository')
            ),
            $this->getContainer()->get(MigrationWriteService::class),
            $this->getContainer()->get(HttpAssetDownloadService::class),
            $this->getContainer()->get(MigrationEnvironmentService::class),
            $this->getContainer()->get('swag_migration_profile.repository'),
            $this->getContainer()->get(MigrationProgressService::class)
        );
    }

    public function testFetchData(): void
    {
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        /** @var $profileRepo RepositoryInterface */
        $profileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profile', 'shopware55'));
        $criteria->addFilter(new EqualsFilter('gateway', 'api'));
        $profileResult = $profileRepo->search($criteria, $context);
        $profileIds = $profileResult->getIds();

        $request = new Request([], [
            'profileName' => Shopware55Profile::PROFILE_NAME,
            'profileId' => array_pop($profileIds),
            'runUuid' => $this->runUuid,
            'gateway' => 'local',
            'entity' => ProductDefinition::getEntityName(),
            'credentialFields' => [
                'endpoint' => 'test',
                'apiUser' => 'test',
                'apiKey' => 'test',
            ],
        ]);
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
