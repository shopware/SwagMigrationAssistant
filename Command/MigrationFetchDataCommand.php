<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationEnvironmentServiceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationFetchDataCommand extends ContainerAwareCommand
{
    // example call: bin/console migration:fetch:data -t 20080911ffff4fffafffffff19830531 -p shopware55 -g local -y product dbHost localhost dbName 5.5 dbUser root dbPassword root

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    /**
     * @var MigrationEnvironmentServiceInterface
     */
    private $environmentService;

    public function __construct(
        MigrationCollectServiceInterface $migrationCollectService,
        MigrationEnvironmentServiceInterface $environmentService,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->migrationCollectService = $migrationCollectService;
        $this->environmentService = $environmentService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fetches data with the given profile from the given gateway')
            ->addOption('tenant-id', 't', InputOption::VALUE_REQUIRED)
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED)
            ->addOption('gateway', 'g', InputOption::VALUE_REQUIRED)
            ->addOption('entity', 'y', InputOption::VALUE_REQUIRED)
            ->addOption('catalog-id', 'c', InputOption::VALUE_REQUIRED)
            ->addOption('sales-channel-id', 's', InputOption::VALUE_REQUIRED)
            ->addArgument('credentials', InputArgument::IS_ARRAY | InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tenantId = $input->getOption('tenant-id');

        if (!$tenantId) {
            throw new InvalidArgumentException('No tenant id provided');
        }
        if (!Uuid::isValid($tenantId)) {
            throw new InvalidArgumentException('Invalid uuid provided');
        }
        $context = Context::createDefaultContext($tenantId);

        $catalogId = $input->getOption('catalog-id');
        if ($catalogId !== null && !Uuid::isValid($catalogId)) {
            throw new InvalidArgumentException('Invalid catalogue uuid provided');
        }

        $salesChannelId = $input->getOption('sales-channel-id');
        if ($salesChannelId !== null && !Uuid::isValid($salesChannelId)) {
            throw new InvalidArgumentException('Invalid sales channel uuid provided');
        }

        $profile = $input->getOption('profile');
        if (!$profile) {
            throw new InvalidArgumentException('No profile provided');
        }

        $gateway = $input->getOption('gateway');
        if (!$gateway) {
            throw new InvalidArgumentException('No gateway provided');
        }

        $entity = $input->getOption('entity');
        if (!$entity) {
            throw new InvalidArgumentException('No entity provided');
        }

        $credentialsItems = $input->getArgument('credentials');

        $credentials = [];
        $credentialsCount = \count($credentialsItems);

        if ($credentialsCount % 2 !== 0) {
            throw new InvalidArgumentException('Invalid number of credential items');
        }

        for ($i = 0; $i < $credentialsCount; $i += 2) {
            $credentials[$credentialsItems[$i]] = $credentialsItems[$i + 1];
        }

        $output->writeln('Fetching data...');

        $migrationContext = new MigrationContext($profile, $gateway, $entity, $credentials, 0, 0);
        $total = $this->environmentService->getEntityTotal($migrationContext);

        $limit = 100;
        $totalImportedCount = 0;
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        for ($offset = 0; $offset < $total; $offset += $limit) {
            $migrationContext = new MigrationContext(
                $profile,
                $gateway,
                $entity,
                $credentials,
                $offset,
                $limit,
                $catalogId,
                $salesChannelId
            );
            $importedCount = $this->migrationCollectService->fetchData($migrationContext, $context);
            $totalImportedCount += $importedCount;
            $progressBar->advance($importedCount);
        }

        $progressBar->finish();

        $output->writeln('');
        $output->writeln('Fetching done.');
        $output->writeln('');
        $output->writeln('Imported: ' . $totalImportedCount);
        $output->writeln('Skipped: ' . ($total - $totalImportedCount));
    }
}
