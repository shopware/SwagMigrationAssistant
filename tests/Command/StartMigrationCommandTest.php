<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagMigrationAssistant\Command\StartMigrationCommand;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Run\RunService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[Package('services-settings')]
class StartMigrationCommandTest extends TestCase
{
    use KernelTestBehaviour;

    private CommandTester $commandTester;

    private Command $command;

    protected function setUp(): void
    {
        $this->setCommand($this->createMock(RunService::class));
    }

    public function testStartMigration(): void
    {
        $result = $this->commandTester->execute([
            'command' => $this->command->getName(),
            'dataSelections' => ['products'],
        ]);

        static::assertSame(Command::SUCCESS, $result);
    }

    public function testStartMigrationWithRunningMigration(): void
    {
        $runService = $this->createMock(RunService::class);
        $runService->expects(static::once())
            ->method('startMigrationRun')
            ->willThrowException(MigrationException::migrationIsAlreadyRunning());

        $this->setCommand($runService);

        $result = $this->commandTester->execute([
            'command' => $this->command->getName(),
            'dataSelections' => ['products'],
        ]);

        static::assertSame(Command::FAILURE, $result);
    }

    public function testStartMigrationWithIncompletePremapping(): void
    {
        $runService = $this->createMock(RunService::class);
        $runService->expects(static::once())
            ->method('startMigrationRun')
            ->willThrowException(MigrationException::premappingIsIncomplete());

        $this->setCommand($runService);

        $result = $this->commandTester->execute([
            'command' => $this->command->getName(),
            'dataSelections' => ['products'],
        ]);

        static::assertSame(Command::FAILURE, $result);
    }

    public function testStartMigrationWithoutDataSelection(): void
    {
        $result = $this->commandTester->execute([
            'command' => $this->command->getName(),
            'dataSelections' => [],
        ]);

        static::assertSame(Command::FAILURE, $result);
        static::assertSame('Please provide at least one data selection.', \trim($this->commandTester->getDisplay()));
    }

    private function setCommand(RunService&MockObject $runService): void
    {
        $kernel = self::getKernel();
        $application = new Application($kernel);

        $application->add(new StartMigrationCommand(
            $runService,
            'migration:start'
        ));
        $this->command = $application->find('migration:start');
        $this->commandTester = new CommandTester($this->command);
    }
}
