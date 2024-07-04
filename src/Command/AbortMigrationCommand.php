<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\NoRunningMigrationException;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Package('services-settings')]
#[AsCommand(
    name: 'migration:abort',
    description: 'Abort the current migration',
)]
class AbortMigrationCommand extends Command
{
    public function __construct(
        private readonly RunServiceInterface $runService,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createCLIContext();

        try {
            $this->runService->abortMigration($context);
        } catch (NoRunningMigrationException $exception) {
            $output->writeln('Currently there is no migration running.');

            return Command::FAILURE;
        }

        $output->writeln('The migration is aborted.');

        return Command::SUCCESS;
    }
}
