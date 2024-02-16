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

    private int $stepSize = 100;

    private OutputInterface $output;

    /**
     * @param EntityRepository<GeneralSettingCollection> $generalSettingRepo
     * @param EntityRepository<SwagMigrationConnectionCollection> $migrationConnectionRepo
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private readonly EntityRepository $generalSettingRepo,
        private readonly EntityRepository $migrationConnectionRepo,
        private readonly EntityRepository $migrationRunRepo,
        private readonly DataSetRegistryInterface $dataSetRegistry,
        private readonly RunServiceInterface $runService,
        private readonly PremappingServiceInterface $premappingService,
        private readonly MigrationDataFetcherInterface $migrationDataFetcher,
        private readonly MigrationDataConverterInterface $migrationDataConverter,
        private readonly MigrationDataWriterInterface $migrationDataWriter,
        private readonly MediaFileProcessorServiceInterface $processorService,
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
            ->addOption('step-size', null, InputOption::VALUE_REQUIRED, 'Step size for all paginated actions', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->output = $output;
        $this->checkOptions($input);

        $context = Context::createDefaultContext();
        /** @var GeneralSettingEntity|null $generalSetting */
        $generalSetting = $this->generalSettingRepo->search(new Criteria(), $context)->first();
        if ($generalSetting === null) {
            throw new \RuntimeException('No settings found');
        }

        $selectedConnectionId = $generalSetting->getSelectedConnectionId();
        if ($selectedConnectionId === null) {
            throw new \InvalidArgumentException('At first please create a connection via the administration.');
        }

        /** @var SwagMigrationConnectionEntity|null $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$selectedConnectionId]), $context)->first();
        if ($connection === null) {
            throw new \InvalidArgumentException(\sprintf('No connection found for ID "%s".', $selectedConnectionId));
        }

        $migrationContext = $this->migrationContextFactory->createByConnection($connection);
        $progressState = $this->runService->createMigrationRun(
            $migrationContext,
            $this->dataSelectionNames,
            $context
        );

        if ($progressState === null) {
            throw new \InvalidArgumentException('Another migration is currently running.');
        }

        $runId = $progressState->getRunId();
        if ($runId === null) {
            throw new \InvalidArgumentException('Migration run could not be created.');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$runId]), $context)->first();
        if ($run === null) {
            throw new \InvalidArgumentException('Migration run could not be created.');
        }

        $migrationContext = $this->migrationContextFactory->create($run);

        if ($migrationContext === null) {
            throw new \InvalidArgumentException('Migration context could not be created.');
        }

        $this->generatePremapping($run, $context);
        $this->fetchData($progressState, $migrationContext, $run, $context);
        $this->writeData($run, $context);
        $this->processMedia($run, $context);
        $this->runService->finishMigration($run->getId(), $context);

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

        $this->stepSize = (int) $input->getOption('step-size');
    }

    private function fetchData(ProgressState $progressState, MigrationContextInterface $migrationContext, SwagMigrationRunEntity $run, Context $context): void
    {
        $this->migrationRunRepo->update([
            [
                'id' => $progressState->getRunId(),
                'progress' => $progressState->getRunProgress(),
            ],
        ], $context);

        foreach ($progressState->getRunProgress() as $progress) {
            foreach ($progress->getEntities() as $entityProgress) {
                $dataSet = $this->dataSetRegistry->getDataSet($migrationContext, $entityProgress->getEntityName());

                if ($entityProgress->getTotal() === 0) {
                    continue;
                }

                $progressBar = new ProgressBar($this->output, $entityProgress->getTotal());
                $progressBar->setFormat('[%bar%] %current%/%max% Read ' . $entityProgress->getEntityName());
                $progressBar->start();

                while ($entityProgress->getCurrentCount() < $entityProgress->getTotal()) {
                    $migrationContext = $this->migrationContextFactory->create(
                        $run,
                        $entityProgress->getCurrentCount(),
                        $this->stepSize,
                        $dataSet::getEntity()
                    );

                    if ($migrationContext === null) {
                        throw new \InvalidArgumentException('Migration context could not be created.');
                    }

                    $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);

                    if (!empty($data)) {
                        $this->migrationDataConverter->convert($data, $migrationContext, $context);
                    }

                    $entityProgress->setCurrentCount($entityProgress->getCurrentCount() + $this->stepSize);
                    if ($entityProgress->getCurrentCount() >= $entityProgress->getTotal()) {
                        $progressBar->setProgress($progressBar->getMaxSteps());
                    } else {
                        $progressBar->advance($this->stepSize);
                    }
                }

                $progressBar->finish();
                $this->output->writeln('');
            }
        }
    }

    private function writeData(SwagMigrationRunEntity $run, Context $context): void
    {
        $writeProgress = $this->runService->calculateWriteProgress($run, $context);

        $this->migrationRunRepo->update([
            [
                'id' => $run->getId(),
                'progress' => $writeProgress,
            ],
        ], $context);

        foreach ($writeProgress as $progress) {
            foreach ($progress['entities'] as $entityProgress) {
                $migrationContext = $this->migrationContextFactory->create(
                    $run,
                    0,
                    100,
                    $entityProgress['entityName']
                );

                if ($migrationContext === null) {
                    throw new \InvalidArgumentException('Migration context could not be created.');
                }

                $dataSet = $this->dataSetRegistry->getDataSet($migrationContext, $entityProgress['entityName']);

                if ($entityProgress['total'] === 0) {
                    continue;
                }

                $progressBar = new ProgressBar($this->output, $entityProgress['total']);
                $progressBar->setFormat('[%bar%] %current%/%max% Write ' . $entityProgress['entityName']);
                $progressBar->start();

                while ($entityProgress['currentCount'] < $entityProgress['total']) {
                    $migrationContext = $this->migrationContextFactory->create(
                        $run,
                        $entityProgress['currentCount'],
                        100,
                        $dataSet::getEntity()
                    );

                    if ($migrationContext === null) {
                        throw new \InvalidArgumentException('Migration context could not be created.');
                    }

                    $this->migrationDataWriter->writeData($migrationContext, $context);

                    $entityProgress['currentCount'] += 100;
                    if ($entityProgress['currentCount'] >= $entityProgress['total']) {
                        $progressBar->setProgress($progressBar->getMaxSteps());
                    } else {
                        $progressBar->advance(100);
                    }
                }

                $progressBar->finish();
                $this->output->writeln('');
            }
        }
    }

    private function processMedia(SwagMigrationRunEntity $run, Context $context): void
    {
        $mediaFilesProgress = $this->runService->calculateMediaFilesProgress($run, $context);

        $this->migrationRunRepo->update([
            [
                'id' => $run->getId(),
                'progress' => $mediaFilesProgress,
            ],
        ], $context);

        foreach ($mediaFilesProgress as $progress) {
            if ($progress['id'] === 'processMediaFiles') {
                foreach ($progress['entities'] as $entityProgress) {
                    $progressBar = new ProgressBar($this->output, $entityProgress['total']);
                    $progressBar->setFormat('[%bar%] %current%/%max% Process ' . $entityProgress['entityName']);
                    $progressBar->start();

                    while ($entityProgress['currentCount'] < $entityProgress['total']) {
                        $migrationContext = $this->migrationContextFactory->create(
                            $run,
                            $entityProgress['currentCount'],
                            100
                        );

                        if ($migrationContext === null) {
                            throw new \InvalidArgumentException('Migration context could not be created.');
                        }

                        $this->processorService->processMediaFiles($migrationContext, $context, 5000);
                        $entityProgress['currentCount'] += 100;
                        if ($entityProgress['currentCount'] >= $entityProgress['total']) {
                            $progressBar->setProgress($progressBar->getMaxSteps());
                        } else {
                            $progressBar->advance(100);
                        }
                    }

                    $progressBar->finish();
                    $this->output->writeln('');
                }
            }
        }

        $this->output->writeln('The download of media files will be executed in the background of the administration.');
        $this->output->writeln('You can also use the cli worker for that with the bin\console "messenger:consume-messages default" command.');
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
                    $this->runService->abortMigration($run->getId(), $context);

                    throw new \InvalidArgumentException('Premapping is incomplete, please fill it in before performing the migration.');
                }
            }
        }
    }
}
