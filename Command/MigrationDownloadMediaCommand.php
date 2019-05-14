<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationAssistant\Command\Event\MigrationMediaDownloadAdvanceEvent;
use SwagMigrationAssistant\Command\Event\MigrationMediaDownloadFinishEvent;
use SwagMigrationAssistant\Command\Event\MigrationMediaDownloadStartEvent;
use SwagMigrationAssistant\Migration\Media\CliMediaDownloadServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MigrationDownloadMediaCommand extends Command implements EventSubscriberInterface
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var CliMediaDownloadServiceInterface
     */
    private $mediaDownloadService;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var string
     */
    private $rundId;

    /**
     * @var Context
     */
    private $context;

    public function __construct(
        CliMediaDownloadServiceInterface $cliMediaDownloadService,
        EntityRepositoryInterface $migrationRunRepo,
        EntityRepositoryInterface $mediaFileRepo
    ) {
        parent::__construct();

        $this->mediaDownloadService = $cliMediaDownloadService;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->mediaFileRepo = $mediaFileRepo;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MigrationMediaDownloadStartEvent::EVENT_NAME => 'onStart',
            MigrationMediaDownloadAdvanceEvent::EVENT_NAME => 'onAdvance',
            MigrationMediaDownloadFinishEvent::EVENT_NAME => 'onFinish',
        ];
    }

    public function onStart(MigrationMediaDownloadStartEvent $event): void
    {
        if ($this->io !== null) {
            $this->io->progressStart($event->getNumberOfFiles());
        }
    }

    public function onAdvance(): void
    {
        if ($this->io !== null) {
            $this->io->progressAdvance();
        }
    }

    public function onFinish(MigrationMediaDownloadFinishEvent $event): void
    {
        if ($this->io !== null) {
            $this->io->progressFinish();
            $this->io->table(
                ['Action', 'Number of files'],
                [
                    ['Migrated', $event->getMigrated()],
                    ['Skipped', $event->getSkipped()],
                ]
            );
        }
    }

    protected function configure(): void
    {
        $this
            ->setName('migration:media:download')
            ->setDescription('Downloads all media')
            ->addOption('run-id', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('catalog-id', 'c', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->context = Context::createDefaultContext();

        $this->rundId = $input->getOption('run-id');
        if (!$this->rundId) {
            throw new \InvalidArgumentException('No run-id provided');
        }

        $this->io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($output);

        $output->writeln('Downloading media...');

        $this->mediaDownloadService->setLogger($logger);
        $this->mediaDownloadService->downloadMedia($this->rundId, $this->context);
        $this->finishRun();

        $output->writeln('Downloading done.');
    }

    private function finishRun(): void
    {
        if (!$this->hasUnprocessedMediaFiles()) {
            $this->migrationRunRepo->update([
                [
                    'id' => $this->rundId,
                    'status' => 'finished',
                ],
            ], $this->context);
        }
    }

    private function hasUnprocessedMediaFiles(): bool
    {
        $writtenCriteria = new Criteria();
        $writtenCriteria->addFilter(new EqualsFilter('runId', $this->rundId));
        $writtenCriteria->addFilter(new EqualsFilter('processed', false));

        return $this->mediaFileRepo->search($writtenCriteria, $this->context)->getTotal() > 0;
    }
}
