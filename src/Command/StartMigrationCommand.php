<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationIsAlreadyRunningException;
use SwagMigrationAssistant\Exception\PremappingIsIncompleteException;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\BasicSettingsDataSelection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Package('services-settings')]
#[AsCommand(
    name: 'migration:start',
    description: 'Migrate the data of your selected source to Shopware 6. Before you execute this command
    you have to configure the migration in the Shopware 6 administration.',
)]
class StartMigrationCommand extends Command
{
    /**
     * @var string[]
     */
    private array $dataSelectionNames = [];

    public function __construct(
        private readonly RunServiceInterface $runService,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('dataSelections', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createCLIContext();

        if (!$this->validateDataSelections($input)) {
            $output->writeln('Please provide at least one data selection.');

            return Command::FAILURE;
        }

        try {
            $this->runService->startMigrationRun($this->dataSelectionNames, $context);
        } catch (MigrationIsAlreadyRunningException $exception) {
            $output->writeln('Migration is already running, please use migration:get-status to check the progress.');

            return Command::FAILURE;
        } catch (PremappingIsIncompleteException $exception) {
            $output->writeln('Premapping is incomplete, please fill it in before performing the migration.');

            return Command::FAILURE;
        }

        $output->writeln('Migration is started, please use migration:get-progress to check the progress.');

        return Command::SUCCESS;
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    private function validateDataSelections(InputInterface $input): bool
    {
        $dataSelections = $input->getArgument('dataSelections');
        if (!$dataSelections) {
            return false;
        }
        if (!\is_array($dataSelections)) {
            $dataSelections = [$dataSelections];
        }
        $this->dataSelectionNames[] = BasicSettingsDataSelection::IDENTIFIER;
        $this->dataSelectionNames = \array_merge($this->dataSelectionNames, $dataSelections);

        return true;
    }
}
