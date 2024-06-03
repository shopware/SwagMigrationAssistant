<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[Package('services-settings')]
class GetMigrationProgressCommand extends Command
{
    private const PROGRESSBAR_FORMAT = '[%bar%] %current%/%max% ';

    public function __construct(
        private readonly RunServiceInterface $runService,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Shows the current progress of a migration')
            ->addOption('refreshRate', 'r', InputOption::VALUE_OPTIONAL, 'Refresh rate in seconds', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();
        $refreshRate = (int) $input->getOption('refreshRate');
        $migrationState = $this->runService->getRunStatus($context);

        if ($migrationState->getStep() === MigrationStep::IDLE) {
            $output->writeln('Currently there is no migration running');

            return Command::FAILURE;
        }

        $progressBar = new ProgressBar($output, $migrationState->getTotal());
        $progressBar->setFormat(self::PROGRESSBAR_FORMAT . $migrationState->getStepValue());
        $progressBar->setMaxSteps($migrationState->getTotal());
        $progressBar->setProgress($migrationState->getProgress());

        while ($migrationState->getStep() !== MigrationStep::FINISHED) {
            $migrationState = $this->runService->getRunStatus($context);

            if (\in_array($migrationState->getStep(), [MigrationStep::ABORTING, MigrationStep::ABORTED, MigrationStep::IDLE], true)) {
                $output->writeln('');
                $output->writeln('Migration was aborted.');

                break;
            }

            if ($migrationState->getStep() === MigrationStep::WAITING_FOR_APPROVE) {
                $this->runService->approveFinishingMigration($context);
                $output->writeln('');
                $output->writeln('Migration is finished.');

                break;
            }

            $progressBar->setFormat(self::PROGRESSBAR_FORMAT . $migrationState->getStepValue());
            $progressBar->setMaxSteps($migrationState->getTotal());
            $progressBar->setProgress($migrationState->getProgress());

            \sleep($refreshRate);
        }

        return Command::SUCCESS;
    }
}
