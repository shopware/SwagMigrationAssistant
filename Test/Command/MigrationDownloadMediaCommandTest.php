<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Command;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Command\MigrationDownloadMediaCommand;
use SwagMigrationNext\Command\MigrationFetchDataCommand;
use SwagMigrationNext\Command\MigrationWriteDataCommand;
use SwagMigrationNext\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\CliMediaDownloadService;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Migration\Writer\MediaWriter;
use SwagMigrationNext\Migration\Writer\WriterRegistry;
use SwagMigrationNext\Test\MigrationServicesTrait;
use SwagMigrationNext\Test\Mock\Migration\Media\DummyCliMediaDownloadService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MigrationDownloadMediaCommandTest extends TestCase
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
     * @var EntityWriterInterface
     */
    private $entityWriter;

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
     * @var CliMediaDownloadService
     */
    private $cliMediaDownloadService;

    /**
     * @var Application
     */
    private $application;

    protected function setUp(): void
    {
        $this->fileSaver = $this->getContainer()->get(FileSaver::class);
        $this->eventDispatcher = new EventDispatcher();

        $dataDefinition = $this->getContainer()->get(SwagMigrationDataDefinition::class);
        $mediaDefinition = $this->getContainer()->get(MediaDefinition::class);
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->entityWriter = $this->getContainer()->get(EntityWriter::class);
        $this->mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');
        $this->migrationProfileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationRunRepo = $this->getContainer()->get('swag_migration_run.repository');

        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->entityWriter,
            $this->getContainer()->get(MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo,
            $dataDefinition
        );

        $this->migrationWriteService = new MigrationDataWriter(
            $this->entityWriter,
            $this->migrationDataRepo,
            new WriterRegistry(
                [
                    new MediaWriter($this->entityWriter, $mediaDefinition),
                ]
            ),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get(LoggingService::class),
            $dataDefinition
        );
        $this->eventDispatcher = new EventDispatcher();

        $this->cliMediaDownloadService = new DummyCliMediaDownloadService(
            $this->mediaFileRepo,
            $this->fileSaver,
            $this->eventDispatcher,
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
        return $this->executeCommand($options, 'migration:media:download');
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
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
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
        ]);

        static::assertStringContainsString('Written: 23', $output);
        static::assertStringContainsString('Skipped: 0', $output);

        $output = $this->executeDownloadCommand([
            '--run-id' => $runId,
        ]);

        static::assertStringContainsString('Downloading done.', $output);
    }

    public function testDownloadDataWithoutRunId(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $kernel = $this->getKernel();
        $application = new Application($kernel);

        $application->add(new MigrationDownloadMediaCommand(
            $this->cliMediaDownloadService,
            $this->migrationRunRepo,
            $this->mediaFileRepo
        ));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No run-id provided');

        $command = $application->find('migration:media:download');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
        ]);
    }

    private function createCommands(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
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

        $downloadCommand = new MigrationDownloadMediaCommand(
            $this->cliMediaDownloadService,
            $this->migrationRunRepo,
            $this->mediaFileRepo
        );

        $events = MigrationDownloadMediaCommand::getSubscribedEvents();
        foreach ($events as $event => $method) {
            $this->eventDispatcher->addListener($event, [$downloadCommand, $method]);
        }

        $this->application->add($downloadCommand);
    }
}
