<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationWriteDataCommand extends ContainerAwareCommand
{
    // example call: bin/console migration:write:data -y product -r 0c5ca6049b9a46a987b510e1c5bde36a

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationWriteService;

    public function __construct(MigrationDataWriterInterface $migrationWriteService, ?string $name = null)
    {
        parent::__construct($name);
        $this->migrationWriteService = $migrationWriteService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Writes data with the given profile')
            ->addOption('catalog-id', 'c', InputOption::VALUE_REQUIRED)
            ->addOption('run-id', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('entity', 'y', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = Context::createDefaultContext();

        $catalogId = $input->getOption('catalog-id');
        if ($catalogId !== null && Uuid::isValid($catalogId)) {
            $context = $context->createWithCatalogIds(array_merge($context->getCatalogIds(), [$catalogId]));
        }

        $runUuid = $input->getOption('run-id');
        if (!$runUuid) {
            throw new InvalidArgumentException('No run-id provided');
        }

        $entity = $input->getOption('entity');
        if (!$entity) {
            throw new InvalidArgumentException('No entity provided');
        }

        $migrationContext = new MigrationContext($runUuid, '', $runUuid, '', $entity, [], 0, 1000, $catalogId);

        $output->writeln('Writing data...');

        $this->migrationWriteService->writeData($migrationContext, $context);

        $output->writeln('Writing done.');
    }
}
