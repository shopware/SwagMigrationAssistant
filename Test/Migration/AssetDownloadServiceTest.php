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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Asset\CliAssetDownloadService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationWriteService;
use SwagMigrationNext\Migration\Service\MigrationWriteServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
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

    protected function setUp()
    {
        $fileSaver = $this->getContainer()->get(FileSaver::class);
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $this->logger = $this->getContainer()->get('logger');
        $migrationMapping = $this->getContainer()->get('swag_migration_mapping.repository');
        $this->mediaRepository = $this->getContainer()->get('media.repository');

        $this->migrationWriteService = $this->getContainer()->get(MigrationWriteService::class);
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->getContainer()->get('swag_migration_data.repository'),
            $this->getContainer()->get('swag_migration_logging.repository'),
            $this->getContainer()->get(Shopware55MappingService::class)
        );

        $this->assetDownloadService = new CliAssetDownloadService($migrationMapping, $fileSaver, $eventDispatcher, $this->logger);
    }

    public function testDownloadAssets(): void
    {
        static::markTestSkipped('needs an correct URL to download the assets from');
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
            '',
            '',
            Shopware55Profile::PROFILE_NAME,
            'local',
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

        self::assertSame(42, $productTotalAfter - $productTotalBefore);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mimeType', null));
        $totalBeforeAssetDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        $this->assetDownloadService->downloadAssets(Shopware55Profile::PROFILE_NAME, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('mimeType', null)]));
        $totalAfterAssetDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        self::assertSame(0, $totalBeforeAssetDownload - $totalAfterAssetDownload);
    }
}
