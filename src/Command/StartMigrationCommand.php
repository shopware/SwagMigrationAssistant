<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\RunServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\BasicSettingsDataSelection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Package('services-settings')]
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
        $this
            ->setDescription('Migrate the data of your selected source to Shopware 6. Before you execute this command
            you have to  configure the migration in the Shopware 6 administration.')
            ->addArgument('dataSelections', InputArgument::IS_ARRAY | InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->checkDataSelections($input);
        $context = Context::createDefaultContext();

        // Todo: Check Premapping, if its done
        // $this->generatePremapping($run, $context);

        $this->runService->startMigrationRun($this->dataSelectionNames, $context);

        $output->writeln('Migration is started, please use migration:get-status to check the progress.');

        return 0;
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    private function checkDataSelections(InputInterface $input): void
    {
        $dataSelections = $input->getArgument('dataSelections');
        if (!$dataSelections) {
            throw new \InvalidArgumentException('No dataSelections entered');
        }
        if (!\is_array($dataSelections)) {
            $dataSelections = [$dataSelections];
        }
        $this->dataSelectionNames[] = BasicSettingsDataSelection::IDENTIFIER;
        $this->dataSelectionNames = \array_merge($this->dataSelectionNames, $dataSelections);
    }

    private function generatePremapping(SwagMigrationRunEntity $run, Context $context): void
    {
        $migrationContext = $this->migrationContextFactory->create($run);

        if ($migrationContext === null) {
            throw new \InvalidArgumentException('Migration context could not be created.');
        }

        $premapping = $this->premappingService->generatePremapping($context, $migrationContext, $run);

        foreach ($premapping as $item) {
            foreach ($item->getMapping() as $mapping) {
                if ($mapping->getDestinationUuid() === '') {
                    $this->runService->abortMigration($context);

                    throw new \InvalidArgumentException('Premapping is incomplete, please fill it in before performing the migration.');
                }
            }
        }
    }
}
