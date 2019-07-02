<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationFetchDataCommand extends Command
{
    // example call: bin/console migration:fetch:data -p shopware55 -g api -y product

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
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    /**
     * @var string
     */
    private $profileName;

    /**
     * @var string
     */
    private $gatewayName;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @var string
     */
    private $profileId;

    /**
     * @var int
     */
    private $limit = 100;

    public function __construct(
        MigrationDataFetcherInterface $migrationCollectService,
        EntityRepositoryInterface $migrationRunRepo,
        EntityRepositoryInterface $migrationProfileRepo,
        EntityRepositoryInterface $migrationDataRepo,
        DataSetRegistryInterface $dataSetRegistry,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->migrationDataFetcher = $migrationCollectService;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->migrationProfileRepo = $migrationProfileRepo;
        $this->migrationDataRepo = $migrationDataRepo;
        $this->dataSetRegistry = $dataSetRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fetches data with the given profile from the given gateway')
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED)
            ->addOption('gateway', 'g', InputOption::VALUE_REQUIRED)
            ->addOption('entity', 'y', InputOption::VALUE_REQUIRED)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $this->checkOptions($input);
        $this->getProfile($context);

        $total = 0; // TODO FIX IT
        $this->createRun($runId, $total, $context);
        $output->writeln(sprintf('Run created: %s', $runId));

        $output->writeln('Fetching data...');
        $this->fetchData($output, $total, $runId, $context);
        $totalImportedCount = $this->getImportedCount($runId, $context);
        $this->updateRun($runId, $total, $totalImportedCount, $context);

        $output->writeln('');
        $output->writeln('Fetching done.');
        $output->writeln('');
        $output->writeln('Imported: ' . $totalImportedCount);
        $output->writeln('Skipped: ' . ($total - $totalImportedCount));
    }

    private function checkOptions(InputInterface $input): void
    {
        $this->profileName = $input->getOption('profile');
        if (!$this->profileName) {
            throw new \InvalidArgumentException('No profile provided');
        }

        $this->gatewayName = $input->getOption('gateway');
        if (!$this->gatewayName) {
            throw new \InvalidArgumentException('No gateway provided');
        }

        $this->entityName = $input->getOption('entity');
        if (!$this->entityName) {
            throw new \InvalidArgumentException('No entity provided');
        }

        $limit = $input->getOption('limit');
        if ($limit !== null) {
            $this->limit = (int) $limit;
        }
    }

    private function getProfile($context): void
    {
        $searchProfileCriteria = new Criteria();
        $searchProfileCriteria->addFilter(new EqualsFilter('name', $this->profileName));
        $searchProfileCriteria->addFilter(new EqualsFilter('gatewayName', $this->gatewayName));
        $profileStruct = $this->migrationProfileRepo->search($searchProfileCriteria, $context)->first();

        if ($profileStruct === null) {
            throw new \InvalidArgumentException('No valid profile found');
        }

        /* @var SwagMigrationProfileEntity $profileStruct */
        $this->credentials = $profileStruct->getCredentialFields();
        $this->profileId = $profileStruct->getId();
    }

    private function createRun($runUuid, $total, $context): void
    {
        $this->migrationRunRepo->create([
            [
                'id' => $runUuid,
                'totals' => [
                    'toBeFetched' => [
                        $this->entityName => $total,
                    ],
                ],
                'profileId' => $this->profileId,
                'status' => SwagMigrationRunEntity::STATUS_RUNNING,
            ],
        ], $context);
    }

    private function fetchData(OutputInterface $output, $total, $runUuid, $context): void
    {
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        $dataSet = $this->dataSetRegistry->getDataSet($this->profileName, $this->entityName);

        for ($offset = 0; $offset < $total; $offset += $this->limit) {
            $migrationContext = new MigrationContext(
                new SwagMigrationConnectionEntity(),
                $runUuid,
                $dataSet,
                $offset,
                $this->limit
            );
            $importedCount = $this->migrationDataFetcher->fetchData($migrationContext, $context);
            $progressBar->advance(\count($importedCount));
        }

        $progressBar->finish();
    }

    private function getImportedCount($runUuid, $context): int
    {
        $importedCriteria = new Criteria();
        $importedCriteria->addFilter(new EqualsFilter('runId', $runUuid));
        $importedCriteria->addFilter(new EqualsFilter('entity', $this->entityName));
        $importedCriteria->addFilter(new EqualsFilter('convertFailure', false));
        $importedCriteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('converted', null)]));

        return $this->migrationDataRepo->search($importedCriteria, $context)->getTotal();
    }

    private function updateRun($runUuid, $total, $totalImportedCount, $context): void
    {
        $this->migrationRunRepo->update([
            [
                'id' => $runUuid,
                'totals' => [
                    'toBeFetched' => [
                        $this->entityName => $total,
                    ],
                    'toBeWritten' => [
                        $this->entityName => $totalImportedCount,
                    ],
                ],
            ],
        ], $context);
    }
}
