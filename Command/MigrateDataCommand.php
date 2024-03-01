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
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionCollection;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationAssistant\Migration\Service\PremappingServiceInterface;
use SwagMigrationAssistant\Migration\Service\ProgressState;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingCollection;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\BasicSettingsDataSelection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[Package('services-settings')]
class MigrateDataCommand extends Command
{
    /**
     * @var string[]
     */
    private array $dataSelectionNames = [];

    private OutputInterface $output;

    private int $refreshRate;

    /**
     * @param EntityRepository<GeneralSettingCollection> $generalSettingRepo
     * @param EntityRepository<SwagMigrationConnectionCollection> $migrationConnectionRepo
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private readonly EntityRepository $generalSettingRepo,
        private readonly EntityRepository $migrationConnectionRepo,
        private readonly RunServiceInterface $runService,
        private readonly PremappingServiceInterface $premappingService,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Migrate the data of your selected source to Shopware 6. Before you execute this command
            you have to  configure the migration in the Shopware 6 administration.')
            ->addArgument('dataSelections', InputArgument::IS_ARRAY | InputArgument::REQUIRED)
            ->addOption('refreshRate', 'r', InputOption::VALUE_OPTIONAL, 'Refresh rate in seconds', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->checkOptions($input);
        $context = Context::createDefaultContext();

        // Todo: Check Premapping, if its done
        //$this->generatePremapping($run, $context);

        $this->runService->startMigrationRun($this->dataSelectionNames, $context);

        $progress = $this->runService->getRunStatus($context);
        $progressBar = new ProgressBar($this->output, $progress->getTotal());
        $progressBar->setFormat('[%bar%] %current%/%max% ' . $progress->getStep());
        $progressBar->start();

        while($progress->getStep() !== MigrationProgress::STATUS_FINISHED) {
            $progress = $this->runService->getRunStatus($context);

            if ($progress->getStep() === MigrationProgress::STATUS_WAITING_FOR_APPROVE) {
                $this->runService->finishMigration($context);
                $this->output->writeln('');
                $this->output->writeln('Migration is finished.');
                break;
            }

            $progressBar->setFormat('[%bar%] %current%/%max% ' . $progress->getStep());
            $progressBar->setMaxSteps($progress->getTotal());
            $progressBar->setProgress($progress->getProgress());

            sleep($this->refreshRate);
        }

        return 0;
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    private function checkOptions(InputInterface $input): void
    {
        $dataSelections = $input->getArgument('dataSelections');
        if (!$dataSelections) {
            throw new \InvalidArgumentException('No dataSelections entered');
        }
        if (!\is_array($dataSelections)) {
            $dataSelections = [$dataSelections];
        }
        $this->dataSelectionNames[] = BasicSettingsDataSelection::IDENTIFIER;
        $this->dataSelectionNames = \array_merge($this->dataSelectionNames, $dataSelections);

        $this->refreshRate = (int) $input->getOption('refreshRate');
    }

    private function generatePremapping(SwagMigrationRunEntity $run, Context $context): void
    {
        $migrationContext = $this->migrationContextFactory->create($run);

        if ($migrationContext === null) {
            throw new \InvalidArgumentException('Migration context could not be created.');
        }

        $premapping = $this->premappingService->generatePremapping($context, $migrationContext, $run);

        foreach ($premapping as $item) {
            foreach ($item->getMapping() as $mapping) {
                if ($mapping->getDestinationUuid() === '') {
                    $this->runService->abortMigration($context);

                    throw new \InvalidArgumentException('Premapping is incomplete, please fill it in before performing the migration.');
                }
            }
        }
    }
}
