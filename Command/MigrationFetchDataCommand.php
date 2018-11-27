<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileStruct;
use SwagMigrationNext\Migration\Service\MigrationDataFetcherInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationFetchDataCommand extends ContainerAwareCommand
{
    // example call: bin/console migration:fetch:data -p shopware55 -g api -y product

    /**
     * @var MigrationDataFetcherInterface
     */
    private $migrationDataFetcher;

    /**
     * @var RepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var RepositoryInterface
     */
    private $migrationProfileRepo;

    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var string
     */
    private $catalogId;

    /**
     * @var string
     */
    private $salesChannelId;

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
        RepositoryInterface $migrationRunRepo,
        RepositoryInterface $migrationProfileRepo,
        RepositoryInterface $migrationDataRepo,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->migrationDataFetcher = $migrationCollectService;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->migrationProfileRepo = $migrationProfileRepo;
        $this->migrationDataRepo = $migrationDataRepo;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fetches data with the given profile from the given gateway')
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED)
            ->addOption('gateway', 'g', InputOption::VALUE_REQUIRED)
            ->addOption('entity', 'y', InputOption::VALUE_REQUIRED)
            ->addOption('catalog-id', 'c', InputOption::VALUE_REQUIRED)
            ->addOption('sales-channel-id', 's', InputOption::VALUE_REQUIRED)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runId = Uuid::uuid4()->getHex();
        $context = Context::createDefaultContext();
        $this->checkOptions($input);
        $this->getProfile($context);

        $total = $this->getEntityTotal();
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
        $this->catalogId = $input->getOption('catalog-id');
        if ($this->catalogId !== null && !Uuid::isValid($this->catalogId)) {
            throw new InvalidArgumentException('Invalid catalog uuid provided');
        }

        $this->salesChannelId = $input->getOption('sales-channel-id');
        if ($this->salesChannelId !== null && !Uuid::isValid($this->salesChannelId)) {
            throw new InvalidArgumentException('Invalid sales channel uuid provided');
        }

        $this->profileName = $input->getOption('profile');
        if (!$this->profileName) {
            throw new InvalidArgumentException('No profile provided');
        }

        $this->gatewayName = $input->getOption('gateway');
        if (!$this->gatewayName) {
            throw new InvalidArgumentException('No gateway provided');
        }

        $this->entityName = $input->getOption('entity');
        if (!$this->entityName) {
            throw new InvalidArgumentException('No entity provided');
        }

        $limit = $input->getOption('limit');
        if ($limit !== null) {
            $this->limit = (int) $limit;
        }
    }

    private function getProfile($context): void
    {
        $searchProfileCriteria = new Criteria();
        $searchProfileCriteria->addFilter(new EqualsFilter('profile', $this->profileName));
        $searchProfileCriteria->addFilter(new EqualsFilter('gateway', $this->gatewayName));
        $profileStruct = $this->migrationProfileRepo->search($searchProfileCriteria, $context)->first();

        if ($profileStruct === null) {
            throw new InvalidArgumentException('No valid profile found');
        }

        /* @var SwagMigrationProfileStruct $profileStruct */
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
                'status' => 'running',
            ],
        ], $context);
    }

    private function fetchData(OutputInterface $output, $total, $runUuid, $context): void
    {
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        for ($offset = 0; $offset < $total; $offset += $this->limit) {
            $migrationContext = new MigrationContext(
                $runUuid,
                $this->profileId,
                $this->profileName,
                $this->gatewayName,
                $this->entityName,
                $this->credentials,
                $offset,
                $this->limit,
                $this->catalogId,
                $this->salesChannelId
            );
            $importedCount = $this->migrationDataFetcher->fetchData($migrationContext, $context);
            $progressBar->advance($importedCount);
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

    private function getEntityTotal(): int
    {
        $migrationContext = new MigrationContext(
            '',
            '',
            $this->profileName,
            $this->gatewayName,
            $this->entityName,
            $this->credentials,
            0,
            0
        );

        return $this->migrationDataFetcher->getEntityTotal($migrationContext);
    }
}
