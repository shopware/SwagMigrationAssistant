<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\MigrationProgressStatus;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[Package('services-settings')]
class GetMigrationProgressCommand extends Command
{
    private const PROGRESSBAR_FORMAT = '[%bar%] %current%/%max% ';

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private readonly EntityRepository $migrationRunRepo,
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
        $run = $this->getCurrentRun($context);
        $refreshRate = (int) $input->getOption('refreshRate');

        if ($run === null) {
            $output->writeln('Currently there is no migration running');

            return Command::FAILURE;
        }

        $progress = $this->runService->getRunStatus($context);
        $progressBar = new ProgressBar($output, $progress->getTotal());
        $progressBar->setFormat(self::PROGRESSBAR_FORMAT . $progress->getStepValue());
        $progressBar->setMaxSteps($progress->getTotal());
        $progressBar->setProgress($progress->getProgress());

        while ($progress->getStep() !== MigrationProgressStatus::FINISHED) {
            $progress = $this->runService->getRunStatus($context);

            if (\in_array($progress->getStep(), [MigrationProgressStatus::ABORTING, MigrationProgressStatus::ABORTED, MigrationProgressStatus::IDLE], true)) {
                $output->writeln('');
                $output->writeln('Migration was aborted.');

                break;
            }

            if ($progress->getStep() === MigrationProgressStatus::WAITING_FOR_APPROVE) {
                $this->runService->approveFinishingMigration($context);
                $output->writeln('');
                $output->writeln('Migration is finished.');

                break;
            }

            $progressBar->setFormat(self::PROGRESSBAR_FORMAT . $progress->getStepValue());
            $progressBar->setMaxSteps($progress->getTotal());
            $progressBar->setProgress($progress->getProgress());

            \sleep($refreshRate);
        }

        return Command::SUCCESS;
    }

    private function getCurrentRun(Context $context): ?SwagMigrationRunEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', SwagMigrationRunEntity::STATUS_RUNNING));
        $criteria->setLimit(1);

        return $this->migrationRunRepo->search($criteria, $context)->getEntities()->first();
    }
}
