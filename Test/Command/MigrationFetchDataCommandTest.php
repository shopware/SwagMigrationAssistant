<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Command;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Command\MigrationFetchDataCommand;
use SwagMigrationNext\Migration\Asset\MediaFileService;
use SwagMigrationNext\Migration\Service\MigrationCollectService;
use SwagMigrationNext\Migration\Service\MigrationEnvironmentService;
use SwagMigrationNext\Profile\Shopware55\Mapping\Shopware55MappingService;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationFetchDataCommandTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var MigrationCollectService
     */
    private $migrationCollectService;

    /**
     * @var MigrationEnvironmentService
     */
    private $environmentService;

    /**
     * @var RepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var RepositoryInterface
     */
    private $migrationProfileRepo;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var RepositoryInterface
     */
    private $loggingRepo;

    protected function setUp(): void
    {
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationCollectService = $this->getMigrationCollectService(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );

        $this->environmentService = $this->getMigrationEnvironmentService(
            $this->migrationDataRepo,
            $this->getContainer()->get(Shopware55MappingService::class),
            $this->getContainer()->get(MediaFileService::class),
            $this->loggingRepo
        );
        $this->migrationRunRepo = $this->getContainer()->get('swag_migration_run.repository');
        $this->migrationProfileRepo = $this->getContainer()->get('swag_migration_profile.repository');
    }

    public function getRequiredOption(): array
    {
        return [
            [['optionName' => 'profile', 'value' => '--profile']],
            [['optionName' => 'gateway', 'value' => '--gateway']],
            [['optionName' => 'entity', 'value' => '--entity']],
        ];
    }

    public function getOption(): array
    {
        return [
            [['optionName' => 'catalog', 'value' => '--catalog-id']],
            [['optionName' => 'sales channel', 'value' => '--sales-channel-id']],
        ];
    }

    public function testFetchData(): void
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

        $command = $application->find('migration:fetch:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Imported: 14', $output);
        $this->assertContains('Skipped: 23', $output);
    }

    /**
     * @dataProvider getRequiredOption
     */
    public function testFetchDataWithoutRequiredOption(array $missingOption): void
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

        $options = [
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
        ];
        unset($options[$missingOption['value']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('No %s provided', $missingOption['optionName']));

        $command = $application->find('migration:fetch:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
    }

    /**
     * @dataProvider getOption
     */
    public function testFetchDataWithInvalidOption(array $option): void
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

        $options = [
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
            $option['value'] => 'invalid-uuid',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid %s uuid provided', $option['optionName']));

        $command = $application->find('migration:fetch:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
    }

    public function testFetchDataWithInvalidUnknown(): void
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

        $options = [
            '--profile' => 'foo',
            '--gateway' => 'local',
            '--entity' => 'product',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No valid profile found');

        $command = $application->find('migration:fetch:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);
    }
}
