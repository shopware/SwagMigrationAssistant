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
use SwagMigrationAssistant\Command\GetMigrationProgressCommand;
use SwagMigrationAssistant\Migration\Run\MigrationState;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GetMigrationProgressCommandTest extends TestCase
{
    use KernelTestBehaviour;

    private const COMMAND_NAME = 'migration:get-progress';

    public function testGetProgressWithFinishingMigration(): void
    {
        $migrationRunService = $this->createMock(RunService::class);
        $migrationRunService
            ->method('getRunStatus')
            ->willReturn(new MigrationState(MigrationStep::FETCHING, 0, 100))
            ->willReturn(new MigrationState(MigrationStep::WAITING_FOR_APPROVE, 0, 100));
        $migrationRunService
            ->expects(static::once())
            ->method('approveFinishingMigration');

        $commandTester = $this->createCommandTester(
            $migrationRunService,
        );

        $result = $commandTester->execute([
            'command' => self::COMMAND_NAME,
        ]);

        static::assertSame(Command::SUCCESS, $result);
    }

    public function testGetProgressWithoutRunningMigration(): void
    {
        $runServiceMock = $this->createMock(RunService::class);
        $runServiceMock->method('getRunStatus')
            ->willReturn(new MigrationState(MigrationStep::IDLE, 0, 0));

        $commandTester = $this->createCommandTester($runServiceMock);

        $result = $commandTester->execute([
            'command' => self::COMMAND_NAME,
        ]);

        static::assertSame(Command::FAILURE, $result);
        static::assertSame('Currently there is no migration running', \trim($commandTester->getDisplay()));
    }

    public function testGetProgressWithAbortingMigration(): void
    {
        $migrationRunService = $this->createMock(RunService::class);
        $migrationRunService
            ->method('getRunStatus')
            ->willReturn(new MigrationState(MigrationStep::FETCHING, 0, 100))
            ->willReturn(new MigrationState(MigrationStep::ABORTING, 0, 100));
        $migrationRunService
            ->expects(static::never())
            ->method('approveFinishingMigration');

        $commandTester = $this->createCommandTester(
            $migrationRunService,
        );

        $result = $commandTester->execute([
            'command' => self::COMMAND_NAME,
        ]);

        static::assertSame(Command::SUCCESS, $result);
        static::assertStringContainsString('Migration was aborted', $commandTester->getDisplay());
    }

    private function createCommandTester(RunService&MockObject $runService): CommandTester
    {
        $kernel = self::getKernel();
        $application = new Application($kernel);

        $application->add(new GetMigrationProgressCommand(
            $runService,
            self::COMMAND_NAME
        ));

        return new CommandTester($application->find(self::COMMAND_NAME));
    }
}
