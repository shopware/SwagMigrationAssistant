<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Command;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Command\MigrationDownloadAssetsCommand;
use SwagMigrationNext\Command\MigrationFetchDataCommand;
use SwagMigrationNext\Command\MigrationWriteDataCommand;
use SwagMigrationNext\Migration\Asset\CliAssetDownloadService;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Migration\Writer\AssetWriter;
use SwagMigrationNext\Migration\Writer\WriterRegistry;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyCliAssetDownloadService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MigrationDownloadAssetsCommandTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationProfileRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationWriteService;

    /**
     * @var FileSaver
     */
    private $fileSaver;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var CliAssetDownloadService
     */
    private $cliAssetDownloadService;

    /**
     * @var Application
     */
    private $application;

    protected function setUp(): void
    {
        $this->fileSaver = $this->getContainer()->get(FileSaver::class);
        $this->eventDispatcher = new EventDispatcher();

        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->mediaRepo = $this->getContainer()->get('media.repository');
        $this->mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $this->migrationProfileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationRunRepo = $this->getContainer()->get('swag_migration_run.repository');

        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );

        $this->migrationWriteService = new MigrationDataWriter(
            $this->migrationDataRepo,
            new WriterRegistry(
                [
                    new AssetWriter($this->mediaRepo),
                ]
            ),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get(LoggingService::class)
        );

        $this->cliAssetDownloadService = new DummyCliAssetDownloadService(
            $this->mediaFileRepo,
            $this->fileSaver,
            new EventDispatcher(),
            new ConsoleLogger(new ConsoleOutput())
        );
    }

    public function executeFetchCommand(array $options): string
    {
        return $this->executeCommand($options, 'migration:fetch:data');
    }

    public function executeWriteCommand(array $options): string
    {
        return $this->executeCommand($options, 'migration:write:data');
    }

    public function executeDownloadCommand(array $options): string
    {
        return $this->executeCommand($options, 'migration:assets:download');
    }

    public function executeCommand(array $options, string $commandName): string
    {
        $command = $this->application->find($commandName);
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);

        return $commandTester->getDisplay();
    }

    public function testDownloadData(): void
    {
        $this->createCommands();

        $output = $this->executeFetchCommand([
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'media',
        ]);

        preg_match('/Run created: ([a-z,0-9]*)/', $output, $matches);
        $runId = $matches[1];

        $output = $this->executeWriteCommand([
            '--run-id' => $runId,
            '--entity' => 'media',
            '--catalog-id' => Uuid::uuid4()->getHex(),
        ]);

        $this->assertStringContainsString('Written: 23', $output);
        $this->assertStringContainsString('Skipped: 0', $output);

        $output = $this->executeDownloadCommand([
            '--run-id' => $runId,
        ]);

        self::assertStringContainsString('Downloading done.', $output);
    }

    public function testDownloadDataWithoutRunId(): void
    {
        $kernel = $this->getKernel();
        $application = new Application($kernel);

        $application->add(new MigrationDownloadAssetsCommand(
            $this->cliAssetDownloadService,
            $this->migrationRunRepo,
            $this->mediaFileRepo
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No run-id provided');

        $command = $application->find('migration:assets:download');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
        ]);
    }

    private function createCommands(): void
    {
        $kernel = $this->getKernel();
        $this->application = new Application($kernel);
        $this->application->add(new MigrationFetchDataCommand(
            $this->migrationDataFetcher,
            $this->migrationRunRepo,
            $this->migrationProfileRepo,
            $this->migrationDataRepo,
            'migration:fetch:data'
        ));
        $this->application->add(new MigrationWriteDataCommand(
            $this->migrationWriteService,
            $this->migrationRunRepo,
            $this->migrationDataRepo,
            $this->mediaFileRepo,
            'migration:write:data'
        ));
        $this->application->add(new MigrationDownloadAssetsCommand(
            $this->cliAssetDownloadService,
            $this->migrationRunRepo,
            $this->mediaFileRepo
        ));
    }
}
