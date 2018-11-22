<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Command;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Command\MigrationFetchDataCommand;
use SwagMigrationNext\Command\MigrationWriteDataCommand;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Logging\LoggingService;
use SwagMigrationNext\Migration\Service\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentServiceInterface;
use SwagMigrationNext\Migration\Service\MigrationWriteService;
use SwagMigrationNext\Migration\Service\MigrationWriteServiceInterface;
use SwagMigrationNext\Migration\Writer\ProductWriter;
use SwagMigrationNext\Migration\Writer\WriterRegistry;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationWriteDataCommandTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var RepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var RepositoryInterface
     */
    private $productRepo;

    /**
     * @var RepositoryInterface
     */
    private $loggingRepo;

    /**
     * @var RepositoryInterface
     */
    private $migrationProfileRepo;

    /**
     * @var MigrationEnvironmentServiceInterface
     */
    private $environmentService;

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var MigrationWriteServiceInterface
     */
    private $migrationWriteService;

    protected function setUp(): void
    {
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->productRepo = $this->getContainer()->get('product.repository');
        $this->migrationProfileRepo = $this->getContainer()->get('swag_migration_profile.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationRunRepo = $this->getContainer()->get('swag_migration_run.repository');

        $this->environmentService = $this->getMigrationEnvironmentService(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );

        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );

        $this->migrationWriteService = new MigrationWriteService(
            $this->migrationDataRepo,
            new WriterRegistry(
                [
                    new ProductWriter($this->productRepo, $this->getContainer()->get(StructNormalizer::class)),
                ]
            ),
            $this->getContainer()->get(MediaFileService::class),
            $this->getContainer()->get(LoggingService::class)
        );
    }

    public function testWriteData(): void
    {
        $kernel = $this->getKernel();
        $application = new Application($kernel);
        $application->add(new MigrationFetchDataCommand(
            $this->migrationCollectService,
            $this->environmentService,
            $this->migrationRunRepo,
            $this->migrationProfileRepo,
            $this->migrationDataRepo,
            'migration:fetch:data'
        ));
        $application->add(new MigrationWriteDataCommand(
            $this->migrationWriteService,
            $this->migrationRunRepo,
            $this->migrationDataRepo,
            'migration:write:data'
        ));

        $command = $application->find('migration:fetch:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
        ]);

        $output = $commandTester->getDisplay();

        preg_match('/Run created: ([a-z,0-9]*)/', $output, $matches);
        $runId = $matches[1];

        $command = $application->find('migration:write:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--run-id' => $runId,
            '--entity' => 'product',
            '--catalog-id' => Uuid::uuid4()->getHex(),
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Written: 14', $output);
        $this->assertContains('Skipped: 0', $output);
    }

    public function testWriteDataWithRunningMigration(): void
    {
        $kernel = $this->getKernel();
        $application = new Application($kernel);
        $application->add(new MigrationFetchDataCommand(
            $this->migrationCollectService,
            $this->environmentService,
            $this->migrationRunRepo,
            $this->migrationProfileRepo,
            $this->migrationDataRepo,
            'migration:fetch:data'
        ));
        $application->add(new MigrationWriteDataCommand(
            $this->migrationWriteService,
            $this->migrationRunRepo,
            $this->migrationDataRepo,
            'migration:write:data'
        ));

        $command = $application->find('migration:fetch:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
        ]);

        $output = $commandTester->getDisplay();

        $command = $application->find('migration:write:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--started-run' => 1,
            '--entity' => 'product',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Written: 14', $output);
        $this->assertContains('Skipped: 0', $output);
    }

    public function testWriteDataWithNoRunningMigration(): void
    {
        $kernel = $this->getKernel();
        $application = new Application($kernel);
        $application->add(new MigrationWriteDataCommand(
            $this->migrationWriteService,
            $this->migrationRunRepo,
            $this->migrationDataRepo,
            'migration:write:data'
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No running migration found');

        $command = $application->find('migration:write:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--started-run' => 1,
            '--entity' => 'product',
        ]);
    }

    public function testWriteDataWithNoRunInformation(): void
    {
        $kernel = $this->getKernel();
        $application = new Application($kernel);
        $application->add(new MigrationWriteDataCommand(
            $this->migrationWriteService,
            $this->migrationRunRepo,
            $this->migrationDataRepo,
            'migration:write:data'
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No run-id provided or started run flag set');

        $command = $application->find('migration:write:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--entity' => 'product',
        ]);
    }

    public function testWriteDataWithNoEntity(): void
    {
        $kernel = $this->getKernel();
        $application = new Application($kernel);
        $application->add(new MigrationWriteDataCommand(
            $this->migrationWriteService,
            $this->migrationRunRepo,
            $this->migrationDataRepo,
            'migration:write:data'
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No entity provided');

        $command = $application->find('migration:write:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--started-run' => 1,
        ]);
    }
}
