<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\NotQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\TermQuery;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Gateway\Shopware55\Local\Shopware55LocalGateway;
use SwagMigrationNext\Migration\Asset\CliAssetDownloadService;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationWriteService;
use SwagMigrationNext\Migration\Service\MigrationWriteServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;

class AssetDownloadServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var CliAssetDownloadService
     */
    private $assetDownloadService;

    /**
     * @var RepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var RepositoryInterface
     */
    private $productRepo;

    /**
     * @var MigrationWriteServiceInterface
     */
    private $migrationWriteService;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

    /**
     * @var string
     */
    private $runUuid;

    protected function setUp()
    {
        $fileSaver = $this->getContainer()->get(FileSaver::class);
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $this->logger = $this->getContainer()->get('logger');
        $migrationMapping = $this->getContainer()->get('swag_migration_mapping.repository');
        $this->mediaRepository = $this->getContainer()->get('media.repository');
        $runRepository = $this->getContainer()->get('swag_migration_run.repository');
        $this->profileUuidService = new MigrationProfileUuidService(
            $this->getContainer()->get('swag_migration_profile.repository'),
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_TYPE
        );
        $this->runUuid = Uuid::uuid4()->getHex();
        $runRepository->create(
            [
                [
                    'id' => $this->runUuid,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                ],
            ],
            Context::createDefaultContext(Defaults::TENANT_ID)
        );

        $this->migrationWriteService = $this->getContainer()->get(MigrationWriteService::class);
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->getContainer()->get('swag_migration_data.repository'),
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get('swag_migration_logging.repository')
        );

        $this->assetDownloadService = new CliAssetDownloadService($migrationMapping, $fileSaver, $eventDispatcher, $this->logger);
    }

    public function testDownloadAssets(): void
    {
        static::markTestSkipped('needs an correct URL to download the assets from');
        $context = Context::createDefaultContext(Defaults::TENANT_ID);

        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_TYPE,
            ProductDefinition::getEntityName(),
            [],
            0,
            250
        );
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();
        $this->migrationCollectService->fetchData($migrationContext, $context);
        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        self::assertEquals(14, $productTotalAfter - $productTotalBefore);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mimeType', null));
        $totalBeforeAssetDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        $this->assetDownloadService->downloadAssets(Shopware55Profile::PROFILE_NAME, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('mimeType', null)]));
        $totalAfterAssetDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        self::assertEquals(21, $totalBeforeAssetDownload - $totalAfterAssetDownload);
    }
}
