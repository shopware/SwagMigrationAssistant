<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriterInterface;
use SwagMigrationAssistant\Migration\Service\PremappingServiceInterface;
use SwagMigrationAssistant\Migration\Service\ProgressState;
use SwagMigrationAssistant\Migration\Setting\GeneralSettingEntity;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\BasicSettingsDataSelection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateDataCommand extends Command
{
    /**
     * @var EntityRepositoryInterface
     */
    private $generalSettingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationConnectionRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var DataSelectionRegistryInterface
     */
    private $dataSelectionRegistry;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    /**
     * @var RunServiceInterface
     */
    private $runService;

    /**
     * @var PremappingServiceInterface
     */
    private $premappingService;

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var MigrationDataConverterInterface
     */
    private $migrationDataConverter;

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationDataWriter;

    /**
     * @var string[]
     */
    private $dataSelectionNames;

    /**
     * @var MediaFileProcessorServiceInterface
     */
    private $processorService;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        EntityRepositoryInterface $generalSettingRepo,
        EntityRepositoryInterface $migrationConnectionRepo,
        EntityRepositoryInterface $migrationRunRepo,
        DataSelectionRegistryInterface $dataSelectionRegistry,
        DataSetRegistryInterface $dataSetRegistry,
        RunServiceInterface $runService,
        PremappingServiceInterface $premappingService,
        MigrationDataFetcherInterface $migrationDataFetcher,
        MigrationDataConverterInterface $migrationDataConverter,
        MigrationDataWriterInterface $migrationDataWriter,
        MediaFileProcessorServiceInterface $processorService,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->generalSettingRepo = $generalSettingRepo;
        $this->migrationConnectionRepo = $migrationConnectionRepo;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->dataSelectionRegistry = $dataSelectionRegistry;
        $this->dataSetRegistry = $dataSetRegistry;
        $this->runService = $runService;
        $this->premappingService = $premappingService;
        $this->migrationDataFetcher = $migrationDataFetcher;
        $this->migrationDataConverter = $migrationDataConverter;
        $this->migrationDataWriter = $migrationDataWriter;
        $this->processorService = $processorService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Migrate the data of your selected source to Shopware 6. Before you execute this command
            you have to  configure the migration in the Shopware 6 administration.')
            ->addArgument('dataSelections', InputArgument::IS_ARRAY | InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->checkOptions($input);

        $context = Context::createDefaultContext();
        /** @var GeneralSettingEntity $generalSetting */
        $generalSetting = $this->generalSettingRepo->search(new Criteria(), $context)->first();

        if ($generalSetting->getSelectedConnectionId() === null) {
            throw new \InvalidArgumentException('Please create first a connection via the administration.');
        }

        /** @var SwagMigrationConnectionEntity $connection */
        $connection = $this->migrationConnectionRepo->search(new Criteria([$generalSetting->getSelectedConnectionId()]), $context)->first();
        $progressState = $this->runService->createMigrationRun($connection->getId(), $this->dataSelectionNames, $context);

        if ($progressState === null) {
            throw new \InvalidArgumentException('Another migration run is currently working.');
        }

        /** @var SwagMigrationRunEntity|null $run */
        $run = $this->migrationRunRepo->search(new Criteria([$progressState->getRunId()]), $context)->first();

        $this->generatePremapping($connection, $run, $context);
        $this->fetchData($progressState, $connection, $context);
        $this->writeData($run, $context);
        $this->processMedia($run, $context);
        $this->runService->finishMigration($run->getId(), $context);
    }

    private function checkOptions(InputInterface $input): void
    {
        $dataSelections = $input->getArgument('dataSelections');
        if (!$dataSelections) {
            throw new \InvalidArgumentException('No dataSelections entered');
        }
        $this->dataSelectionNames[] = BasicSettingsDataSelection::IDENTIFIER;
        $this->dataSelectionNames = array_merge($this->dataSelectionNames, $dataSelections);
    }

    private function fetchData(ProgressState $progressState, SwagMigrationConnectionEntity $connection, Context $context): void
    {
        foreach ($progressState->getRunProgress() as $progress) {
            foreach ($progress->getEntities() as $entityProgress) {
                $dataSet = $this->dataSetRegistry->getDataSet($connection->getProfileName(), $entityProgress->getEntityName());

                if ($entityProgress->getTotal() === 0) {
                    continue;
                }

                $progressBar = new ProgressBar($this->output, $entityProgress->getTotal());
                $progressBar->setFormat('[%bar%] %current%/%max% Read ' . $entityProgress->getEntityName());
                $progressBar->start();

                while ($entityProgress->getCurrentCount() < $entityProgress->getTotal()) {
                    $migrationContext = new MigrationContext(
                        $connection,
                        $progressState->getRunId(),
                        $dataSet,
                        $entityProgress->getCurrentCount(),
                        100
                    );

                    $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);

                    if (!empty($data)) {
                        $this->migrationDataConverter->convert($data, $migrationContext, $context);
                    }

                    $progressBar->advance(100);
                    $entityProgress->setCurrentCount($entityProgress->getCurrentCount() + 100);
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
                $dataSet = $this->dataSetRegistry->getDataSet($run->getConnection()->getProfileName(), $entityProgress['entityName']);

                if ($entityProgress['total'] === 0) {
                    continue;
                }

                $progressBar = new ProgressBar($this->output, $entityProgress['total']);
                $progressBar->setFormat('[%bar%] %current%/%max% Write ' . $entityProgress['entityName']);
                $progressBar->start();

                while ($entityProgress['currentCount'] < $entityProgress['total']) {
                    $migrationContext = new MigrationContext(
                        null,
                        $run->getId(),
                        $dataSet,
                        $entityProgress['currentCount'],
                        100
                    );

                    $this->migrationDataWriter->writeData($migrationContext, $context);

                    $progressBar->advance(100);
                    $entityProgress['currentCount'] += 100;
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
                    while ($entityProgress['currentCount'] < $entityProgress['total']) {
                        $migrationContext = new MigrationContext(
                            $run->getConnection(),
                            $run->getId(),
                            null,
                            $entityProgress['currentCount'],
                            100
                        );

                        $this->processorService->processMediaFiles($migrationContext, $context, 5000);
                        $entityProgress['currentCount'] += 100;
                    }
                }
            }
        }

        $this->output->writeln('The download of media files will be executed in the background of the administration.');
    }

    private function generatePremapping(SwagMigrationConnectionEntity $connection, SwagMigrationRunEntity $run, Context $context): void
    {
        $migrationContext = new MigrationContext(
            $connection,
            $run->getId()
        );

        $premapping = $this->premappingService->generatePremapping($context, $migrationContext, $run);

        foreach ($premapping as $item) {
            foreach ($item->getMapping() as $mapping) {
                if ($mapping->getDestinationUuid() === '') {
                    throw new \InvalidArgumentException('Premapping is incomplete, please fill the premapping before you execute migration.');
                }
            }
        }
    }
}
