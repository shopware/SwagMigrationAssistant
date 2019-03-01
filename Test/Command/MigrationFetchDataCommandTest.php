<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Command;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use SwagMigrationNext\Command\MigrationFetchDataCommand;
use SwagMigrationNext\Migration\Mapping\MappingService;
use SwagMigrationNext\Migration\Media\MediaFileService;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationNext\Test\MigrationServicesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationFetchDataCommandTest extends TestCase
{
    use MigrationServicesTrait,
        IntegrationTestBehaviour;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

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
    private $loggingRepo;

    protected function setUp(): void
    {
        $this->loggingRepo = $this->getContainer()->get('swag_migration_logging.repository');
        $this->migrationDataRepo = $this->getContainer()->get('swag_migration_data.repository');
        $this->migrationDataFetcher = $this->getMigrationDataFetcher(
            $this->migrationDataRepo,
            $this->getContainer()->get(MappingService::class),
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

    public function testFetchData(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $output = $this->runFetchCommand([
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
            '--limit' => 100,
        ]);

        static::assertStringContainsString('Imported: 14', $output);
        static::assertStringContainsString('Skipped: 23', $output);
    }

    /**
     * @dataProvider getRequiredOption
     */
    public function testFetchDataWithoutRequiredOption(array $missingOption): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $options = [
            '--profile' => 'shopware55',
            '--gateway' => 'local',
            '--entity' => 'product',
        ];
        unset($options[$missingOption['value']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('No %s provided', $missingOption['optionName']));

        $this->runFetchCommand($options);
    }

    public function testFetchDataWithInvalidUnknown(): void
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $options = [
            '--profile' => 'foo',
            '--gateway' => 'local',
            '--entity' => 'product',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No valid profile found');

        $this->runFetchCommand($options);
    }

    private function runFetchCommand(array $options): string
    {
        static::markTestSkipped('Reason: New Run-Connection-Profile-Association');
        $kernel = $this->getKernel();
        $application = new Application($kernel);
        $application->add(new MigrationFetchDataCommand(
            $this->migrationDataFetcher,
            $this->migrationRunRepo,
            $this->migrationProfileRepo,
            $this->migrationDataRepo,
            'migration:fetch:data'
        ));

        $command = $application->find('migration:fetch:data');
        $commandTester = new CommandTester($command);
        $commandTester->execute($options);

        return $commandTester->getDisplay();
    }
}
