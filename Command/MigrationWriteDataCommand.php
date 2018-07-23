<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\MigrationWriteServiceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationWriteDataCommand extends ContainerAwareCommand
{
    // example call: bin/console migration:write:data -t ffffffffffffffffffffffffffffffff -p shopware55 -y product

    /**
     * @var MigrationWriteServiceInterface
     */
    private $migrationWriteService;

    public function __construct(MigrationWriteServiceInterface $migrationWriteService, ?string $name = null)
    {
        parent::__construct($name);
        $this->migrationWriteService = $migrationWriteService;
    }

    protected function configure(): void
    {
        $this->setDescription('Writes data with the given profile');
        $this->addOption('tenant-id', 't', InputOption::VALUE_REQUIRED);
        $this->addOption('profileName', 'p', InputOption::VALUE_REQUIRED);
        $this->addOption('entityName', 'y', InputOption::VALUE_REQUIRED);
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

        $entityName = $input->getOption('entityName');
        if (!$entityName) {
            throw new \InvalidArgumentException('No entity name provided');
        }

        $migrationContext = new MigrationContext($profileName, '', $entityName, []);

        $output->writeln('Writing data...');

        $this->migrationWriteService->writeData($migrationContext, $context);

        $output->writeln('Writing done.');
    }
}
