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
use SwagMigrationAssistant\Command\AbortMigrationCommand;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Run\RunService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AbortMigrationCommandTest extends TestCase
{
    use KernelTestBehaviour;

    private CommandTester $commandTester;

    private Command $command;

    protected function setUp(): void
    {
        $this->setCommand($this->createMock(RunService::class));
    }

    public function testAbortMigration(): void
    {
        $result = $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        static::assertSame(Command::SUCCESS, $result);
    }

    public function testStartMigrationWithRunningMigration(): void
    {
        $runService = $this->createMock(RunService::class);
        $runService->expects(static::once())
            ->method('abortMigration')
            ->willThrowException(MigrationException::noRunningMigration());

        $this->setCommand($runService);

        $result = $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        static::assertSame(Command::FAILURE, $result);
    }

    private function setCommand(RunService&MockObject $runService): void
    {
        $kernel = self::getKernel();
        $application = new Application($kernel);

        $application->add(new AbortMigrationCommand(
            $runService,
            'migration:abort'
        ));
        $this->command = $application->find('migration:abort');
        $this->commandTester = new CommandTester($this->command);
    }
}
