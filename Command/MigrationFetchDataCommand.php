<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\MigrationCollectServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationFetchDataCommand extends ContainerAwareCommand
{
    // example call: bin/console migration:fetch:data -t ffffffffffffffffffffffffffffffff -p shopware55 -g local -y product dbHost localhost dbName 5.5 dbUser root dbPassword root

    /**
     * @var MigrationCollectServiceInterface
     */
    private $migrationCollectService;

    public function __construct(MigrationCollectServiceInterface $migrationCollectService, ?string $name = null)
    {
        parent::__construct($name);
        $this->migrationCollectService = $migrationCollectService;
    }

    protected function configure(): void
    {
        $this->setDescription('Fetches data with the given profile from the given gateway');
        $this->addOption('tenant-id', 't', InputOption::VALUE_REQUIRED);
        $this->addOption('profileName', 'p', InputOption::VALUE_REQUIRED);
        $this->addOption('gatewayName', 'g', InputOption::VALUE_REQUIRED);
        $this->addOption('entityName', 'y', InputOption::VALUE_REQUIRED);
        $this->addArgument('credentials', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tenantId = $input->getOption('tenant-id');

        if (!$tenantId) {
            throw new \InvalidArgumentException('No tenant id provided');
        }
        if (!Uuid::isValid($tenantId)) {
            throw new \Exception('Invalid uuid provided');
        }
        $context = Context::createDefaultContext($tenantId);

        $profileName = $input->getOption('profileName');
        if (!$profileName) {
            throw new \InvalidArgumentException('No profile name provided');
        }

        $gatewayName = $input->getOption('gatewayName');
        if (!$gatewayName) {
            throw new \InvalidArgumentException('No gateway name provided');
        }

        $entityName = $input->getOption('entityName');
        if (!$entityName) {
            throw new \InvalidArgumentException('No entity name provided');
        }

        $credentialsItems = $input->getArgument('credentials');

        $credentials = [];
        $credentialsCount = \count($credentialsItems);

        if ($credentialsCount % 2 !== 0) {
            throw new \InvalidArgumentException('Invalid number of credential items');
        }

        for ($i = 0; $i < $credentialsCount; $i += 2) {
            $credentials[$credentialsItems[$i]] = $credentialsItems[$i + 1];
        }

        $migrationContext = new MigrationContext($profileName, $gatewayName, $entityName, $credentials);

        $output->writeln('Fetching data...');

        $this->migrationCollectService->fetchData($migrationContext, $context);

        $output->writeln('Fetching done.');
    }
}
