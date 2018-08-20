<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use Doctrine\DBAL\Driver\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Upload\MediaUpdater;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\NotQuery;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use SwagMigrationNext\Migration\AssetDownloadService;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationWriteService;
use SwagMigrationNext\Migration\MigrationWriteServiceInterface;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssetDownloadServiceTest extends KernelTestCase
{
    use MigrationServicesTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AssetDownloadService
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
        parent::setUp();

        self::bootKernel();

        $this->connection = self::$container->get(Connection::class);
        $this->connection->beginTransaction();

        $mediaUpdater = self::$container->get(MediaUpdater::class);
        $eventDispatcher = self::$container->get('event_dispatcher');
        $this->logger = self::$container->get('logger');
        $migrationMapping = self::$container->get('swag_migration_mapping.repository');
        $this->mediaRepository = self::$container->get('media.repository');

        $this->migrationWriteService = self::$container->get(MigrationWriteService::class);
        $this->productRepo = self::$container->get('product.repository');
        $this->migrationCollectService = $this->getMigrationCollectService(
            self::$container->get('swag_migration_data.repository'),
            self::$container->get(Shopware55MappingService::class)
        );

        $this->assetDownloadService = new AssetDownloadService($migrationMapping, $mediaUpdater, $eventDispatcher, $this->logger);
    }

    protected function tearDown()
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testDownloadAssets(): void
    {
        static::markTestSkipped('needs an correct URL to download the assets from');
        $context = Context::createDefaultContext(Defaults::TENANT_ID);
        $migrationContext = new MigrationContext(
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

        self::assertEquals(42, $productTotalAfter - $productTotalBefore);

        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('mimeType', null));
        $totalBeforeAssetDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        $this->assetDownloadService->downloadAssets($context);

        $criteria = new Criteria();
        $criteria->addFilter(new NotQuery([new TermQuery('mimeType', null)]));
        $totalAfterAssetDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        self::assertEquals(0, $totalBeforeAssetDownload - $totalAfterAssetDownload);
    }
}
