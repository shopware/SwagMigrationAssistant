<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use SwagMigrationAssistant\Command\GetMigrationProgressCommand;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationProgressStatus;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunDefinition;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GetMigrationProgressCommandTest extends TestCase
{
    use KernelTestBehaviour;

    private const COMMAND_NAME = 'migration:get-progress';

    public function testGetProgressWithFinishingMigration(): void
    {
        $runEntity = new SwagMigrationRunEntity();
        $runEntity->setId(Uuid::randomHex());

        $migrationRunService = $this->createMock(RunService::class);
        $migrationRunService
            ->method('getRunStatus')
            ->willReturn(new MigrationProgress(MigrationProgressStatus::FETCHING, 0, 100, new ProgressDataSetCollection([]), 'product', 0))
            ->willReturn(new MigrationProgress(MigrationProgressStatus::WAITING_FOR_APPROVE, 0, 100, new ProgressDataSetCollection([]), 'product', 0));
        $migrationRunService
            ->expects(static::once())
            ->method('approveFinishingMigration');

        /** @var StaticEntityRepository<SwagMigrationRunCollection> $runRepository */
        $runRepository = new StaticEntityRepository([
            new SwagMigrationRunCollection([$runEntity]),
        ], new SwagMigrationRunDefinition());

        $commandTester = $this->createCommandTester(
            $migrationRunService,
            $runRepository
        );

        $result = $commandTester->execute([
            'command' => self::COMMAND_NAME,
        ]);

        static::assertSame(Command::SUCCESS, $result);
    }

    public function testGetProgressWithoutRunningMigration(): void
    {
        /** @var StaticEntityRepository<SwagMigrationRunCollection> $runRepository */
        $runRepository = new StaticEntityRepository([
            new SwagMigrationRunCollection([]),
        ], new SwagMigrationRunDefinition());

        $commandTester = $this->createCommandTester(
            $this->createMock(RunService::class),
            $runRepository
        );

        $result = $commandTester->execute([
            'command' => self::COMMAND_NAME,
        ]);

        static::assertSame(Command::FAILURE, $result);
        static::assertSame('Currently there is no migration running', \trim($commandTester->getDisplay()));
    }

    public function testGetProgressWithAbortingMigration(): void
    {
        $runEntity = new SwagMigrationRunEntity();
        $runEntity->setId(Uuid::randomHex());

        $migrationRunService = $this->createMock(RunService::class);
        $migrationRunService
            ->method('getRunStatus')
            ->willReturn(new MigrationProgress(MigrationProgressStatus::FETCHING, 0, 100, new ProgressDataSetCollection([]), 'product', 0))
            ->willReturn(new MigrationProgress(MigrationProgressStatus::ABORTING, 0, 100, new ProgressDataSetCollection([]), 'product', 0));
        $migrationRunService
            ->expects(static::never())
            ->method('approveFinishingMigration');

        /** @var StaticEntityRepository<SwagMigrationRunCollection> $runRepository */
        $runRepository = new StaticEntityRepository([
            new SwagMigrationRunCollection([$runEntity]),
        ], new SwagMigrationRunDefinition());

        $commandTester = $this->createCommandTester(
            $migrationRunService,
            $runRepository
        );

        $result = $commandTester->execute([
            'command' => self::COMMAND_NAME,
        ]);

        static::assertSame(Command::SUCCESS, $result);
        static::assertStringContainsString('Migration was aborted', $commandTester->getDisplay());
    }

    /**
     * @param StaticEntityRepository<SwagMigrationRunCollection> $runRepository
     */
    private function createCommandTester(RunService&MockObject $runService, StaticEntityRepository $runRepository): CommandTester
    {
        $kernel = self::getKernel();
        $application = new Application($kernel);

        $application->add(new GetMigrationProgressCommand(
            $runRepository,
            $runService,
            self::COMMAND_NAME
        ));

        return new CommandTester($application->find(self::COMMAND_NAME));
    }
}
