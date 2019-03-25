<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationNext\Migration\Service\MigrationDataWriterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationWriteDataCommand extends Command
{
    // example call: bin/console migration:write:data -y product -r 0c5ca6049b9a46a987b510e1c5bde36a

    /**
     * @var MigrationDataWriterInterface
     */
    private $migrationWriteService;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var bool
     */
    private $startedRunFlag;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var int
     */
    private $limit = 100;

    public function __construct(
        MigrationDataWriterInterface $migrationWriteService,
        EntityRepositoryInterface $migrationRunRepo,
        EntityRepositoryInterface $migrationDataRepo,
        EntityRepositoryInterface $mediaFileRepo,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->migrationWriteService = $migrationWriteService;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->migrationDataRepo = $migrationDataRepo;
        $this->mediaFileRepo = $mediaFileRepo;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Writes data with the given profile')
            ->addOption('run-id', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('started-run', 's', InputOption::VALUE_NONE)
            ->addOption('entity', 'y', InputOption::VALUE_REQUIRED)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->context = Context::createDefaultContext();
        $this->checkOptions($input);
        $totalConvertedCount = $this->getConvertedCount();

        $output->writeln('Writing data...');

        $this->writeData($output, $totalConvertedCount, $this->context);
        $totalWrittenCount = $this->getWrittenCount();
        $this->finishRun();

        $output->writeln('');
        $output->writeln('Writing done.');
        $output->writeln('');
        $output->writeln('Written: ' . $totalWrittenCount);
        $output->writeln('Skipped: ' . ($totalConvertedCount - $totalWrittenCount));
    }

    private function writeData(OutputInterface $output, $total, $context): void
    {
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        for ($offset = 0; $offset < $total; $offset += $this->limit) {
            $migrationContext = new MigrationContext(
                new SwagMigrationConnectionEntity(), // TODO FIX IT
                $this->runId,
                null,
                $offset,
                $this->limit
            );
            $this->migrationWriteService->writeData($migrationContext, $context);

            if ($offset + $this->limit > $total) {
                $progressBar->finish();
            } else {
                $progressBar->advance($this->limit);
            }
        }
    }

    private function checkOptions(InputInterface $input): void
    {
        $this->runId = $input->getOption('run-id');
        $this->startedRunFlag = $input->getOption('started-run');
        if (!$this->runId && !$this->startedRunFlag) {
            throw new \InvalidArgumentException('No run-id provided or started run flag set');
        }

        $this->entityName = $input->getOption('entity');
        if (!$this->entityName) {
            throw new \InvalidArgumentException('No entity provided');
        }

        if ($this->startedRunFlag) {
            $startedRunCriteria = new Criteria();
            $startedRunCriteria->addFilter(new EqualsFilter('status', 'running'));
            $startedRunCriteria->setLimit(1);
            $startedRunStruct = $this->migrationRunRepo->search($startedRunCriteria, $this->context)->first();

            if ($startedRunStruct === null) {
                throw new \InvalidArgumentException('No running migration found');
            }

            /* @var SwagMigrationRunEntity $startedRunStruct */
            $this->runId = $startedRunStruct->getId();
        }

        $limit = $input->getOption('limit');
        if ($limit !== null) {
            $this->limit = (int) $limit;
        }
    }

    private function getConvertedCount(): int
    {
        $convertedCriteria = new Criteria();
        $convertedCriteria->addFilter(new EqualsFilter('runId', $this->runId));
        $convertedCriteria->addFilter(new EqualsFilter('entity', $this->entityName));
        $convertedCriteria->addFilter(new EqualsFilter('convertFailure', false));
        $convertedCriteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('converted', null)]));

        return $this->migrationDataRepo->search($convertedCriteria, $this->context)->getTotal();
    }

    private function getWrittenCount(): int
    {
        $writtenCriteria = new Criteria();
        $writtenCriteria->addFilter(new EqualsFilter('runId', $this->runId));
        $writtenCriteria->addFilter(new EqualsFilter('entity', $this->entityName));
        $writtenCriteria->addFilter(new EqualsFilter('convertFailure', false));
        $writtenCriteria->addFilter(new EqualsFilter('writeFailure', false));
        $writtenCriteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('converted', null)]));

        return $this->migrationDataRepo->search($writtenCriteria, $this->context)->getTotal();
    }

    private function finishRun(): void
    {
        if (!$this->hasUnprocessedMediaFiles()) {
            $this->migrationRunRepo->update([
                [
                    'id' => $this->runId,
                    'status' => 'finished',
                ],
            ], $this->context);
        }
    }

    private function hasUnprocessedMediaFiles(): bool
    {
        $writtenCriteria = new Criteria();
        $writtenCriteria->addFilter(new EqualsFilter('runId', $this->runId));
        $writtenCriteria->addFilter(new EqualsFilter('processed', false));

        return $this->mediaFileRepo->search($writtenCriteria, $this->context)->getTotal() > 0;
    }
}
