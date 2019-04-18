<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Command;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Command\MigrationFetchDataCommand;
use SwagMigrationNext\Command\MigrationWriteDataCommand;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Migration\Service\MigrationDataWriter;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationNext\Migration\Writer\CategoryWriter;
use SwagMigrationNext\Migration\Writer\ProductWriter;
use SwagMigrationNext\Migration\Writer\WriterRegistry;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationWriteDataCommandTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

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
    private $loggingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationProfileRepo;

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
     * @var Application
     */
    private $application;

    protected function setUp(): void
    {
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->entityWriter = $this->getContainer()->get(EntityWriter::class);
        $this->migrationProfileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationRunRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->mediaFileRepo = $this->getContainer()->get('swag_migration_media_file.repository');

        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->migrationDataRepo,
            $this->getContainer()->get(MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );

        $this->migrationWriteService = new MigrationDataWriter(
            $this->migrationDataRepo,
            new WriterRegistry(
                [
                    new ProductWriter($this->entityWriter),
                    new CategoryWriter($this->entityWriter),
                ]
            ),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get(LoggingService::class)
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

    public function executeCommand(array $options, string $commandName): string
    {
        $command = $this->application->find($commandName);
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);

        return $commandTester->getDisplay();
    }

    public function testWriteData(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $context = Context::createDefaultContext();

        $this->createCommands();
        $output = $this->executeFetchCommand([
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
        ]);

        preg_match('/Run created: ([a-z,0-9]*)/', $output, $matches);
        $runId = $matches[1];

        $output = $this->executeWriteCommand([
            '--run-id' => $runId,
            '--entity' => 'product',
            '--limit' => 100,
        ]);

        static::assertStringContainsString('Written: 14', $output);
        static::assertStringContainsString('Skipped: 0', $output);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $runId));
        /** @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search($criteria, $context)->first();
        static::assertStringContainsString($run->getStatus(), SwagMigrationRunEntity::STATUS_RUNNING);
    }

    public function testWriteDataWithRunningMigration(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $context = Context::createDefaultContext();
        $this->createCommands();

        $this->executeFetchCommand([
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'category',
        ]);

        $output = $this->executeWriteCommand([
            '--started-run' => 1,
            '--entity' => 'category',
        ]);

        static::assertStringContainsString('Written: 8', $output);
        static::assertStringContainsString('Skipped: 0', $output);

        /** @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria(), $context)->first();
        static::assertStringContainsString($run->getStatus(), SwagMigrationRunEntity::STATUS_FINISHED);
    }

    public function testWriteDataWithNoRunningMigration(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $this->createCommands();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No running migration found');

        $this->executeWriteCommand([
            '--started-run' => 1,
            '--entity' => 'product',
        ]);
    }

    public function testWriteDataWithNoRunInformation(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $this->createCommands();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No run-id provided or started run flag set');

        $this->executeWriteCommand([
            '--entity' => 'product',
        ]);
    }

    public function testWriteDataWithNoEntity(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $this->createCommands();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No entity provided');

        $this->executeWriteCommand([
            '--started-run' => 1,
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
    }
}
