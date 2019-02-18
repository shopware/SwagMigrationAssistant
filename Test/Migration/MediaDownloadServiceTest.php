<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Migration\Media\CliMediaDownloadService;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Migration\Services\MigrationProfileUuidService;
use SwagMigrationNext\Test\MigrationServicesTrait;

class MediaDownloadServiceTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

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
     * @var MigrationProfileUuidService
     */
    private $profileUuidService;

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
        $this->profileUuidService = new MigrationProfileUuidService(
            $this->getContainer()->get('swag_migration_profile.repository'),
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_NAME
        );
        $this->runUuid = Uuid::uuid4()->getHex();
        $runRepository->create(
            [
                [
                    'id' => $this->runUuid,
                    'status' => SwagMigrationRunEntity::STATUS_RUNNING,
                    'profileId' => $this->profileUuidService->getProfileUuid(),
                ],
            ],
            Context::createDefaultContext()
        );

        $this->migrationWriteService = $this->getContainer()->get(MigrationDataWriter::class);
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->getContainer()->get('swag_migration_data.repository'),
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get('swag_migration_logging.repository')
        );

        $this->mediaDownloadService = new CliMediaDownloadService($migrationMapping, $fileSaver, $eventDispatcher, $this->logger);
    }

    public function testDownloadMedia(): void
    {
        static::markTestSkipped('needs an correct URL to download the media from');
        $context = Context::createDefaultContext();

        $migrationContext = new MigrationContext(
            $this->runUuid,
            $this->profileUuidService->getProfileUuid(),
            Shopware55Profile::PROFILE_NAME,
            Shopware55LocalGateway::GATEWAY_NAME,
            ProductDefinition::getEntityName(),
            0,
            250
        );
        $criteria = new Criteria();
        $productTotalBefore = $this->productRepo->search($criteria, $context)->getTotal();
        $this->migrationDataFetcher->fetchData($migrationContext, $context);
        $this->migrationWriteService->writeData($migrationContext, $context);
        $productTotalAfter = $this->productRepo->search($criteria, $context)->getTotal();

        self::assertEquals(14, $productTotalAfter - $productTotalBefore);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mimeType', null));
        $totalBeforeMediaDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        $this->mediaDownloadService->downloadMedia(Shopware55Profile::PROFILE_NAME, $context);

        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('mimeType', null)]));
        $totalAfterMediaDownload = $this->mediaRepository->search($criteria, $context)->getTotal();

        self::assertEquals(21, $totalBeforeMediaDownload - $totalAfterMediaDownload);
    }
}
