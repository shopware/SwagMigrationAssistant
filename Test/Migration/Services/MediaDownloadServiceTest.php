<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Migration;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\CliMediaDownloadService;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriter;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Test\MigrationServicesTrait;

class MediaDownloadServiceTest extends TestCase
{
    use MigrationServicesTrait;
    use IntegrationTestBehaviour;

    /**
     * @var CliMediaDownloadService
     */
    private $mediaDownloadService;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepo;

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationWriteService;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $runUuid;

    protected function setUp(): void
    {
        $fileSaver = $this->getContainer()->get(FileSaver::class);
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $this->logger = $this->getContainer()->get('logger');
        $migrationMapping = $this->getContainer()->get('swag_migration_mapping.repository');
        $this->mediaRepository = $this->getContainer()->get('media.repository');
        $runRepository = $this->getContainer()->get('swag_migration_run.repository');
        $this->runUuid = Uuid::randomHex();
        $runRepository->create(
            [
                [
                    'id' => $this->runUuid,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'profile' => Shopware55Profile::PROFILE_NAME,
                    'gateway' => Shopware55LocalGateway::GATEWAY_NAME,
                ],
            ],
            Context::createDefaultContext()
        );

        $this->migrationWriteService = $this->getContainer()->get(MigrationDataWriter::class);
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get(EntityWriter::class),
            $this->getContainer()->get(MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get('swag_migration_logging.repository'),
            $this->getContainer()->get(SwagMigrationDataDefinition::class),
            $this->getContainer()->get(DataSetRegistry::class)
        );

        $this->mediaDownloadService = new CliMediaDownloadService($migrationMapping, $fileSaver, $eventDispatcher, $this->logger);
    }

    public function testDownloadMedia(): void
    {
        static::markTestSkipped('needs an correct URL to download the media from');
        $context = Context::createDefaultContext();

        $migrationContext = new MigrationContext(
            null,
            $this->runUuid,
            new ProductDataSet(),
            0,
            250
        );
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        static::assertEquals(14, $productTotalAfter - $productTotalBefore);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mimeType', null));
        $totalBeforeMediaDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        $this->mediaDownloadService->downloadMedia(Shopware55Profile::PROFILE_NAME, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('mimeType', null)]));
        $totalAfterMediaDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        static::assertEquals(21, $totalBeforeMediaDownload - $totalAfterMediaDownload);
    }
}
