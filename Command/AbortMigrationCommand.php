<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbortMigrationCommand extends Command
{
    public function __construct(
        private readonly RunServiceInterface $runService,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Abort the current migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();

        $this->runService->abortMigration($context);

        $output->writeln('The migration is aborted.');

        return 0;
    }
}
