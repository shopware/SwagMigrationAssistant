<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use InvalidArgumentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadAdvanceEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadFinishEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadStartEvent;
use SwagMigrationNext\Migration\Asset\CliAssetDownloadServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MigrationDownloadAssetsCommand extends Command implements EventSubscriberInterface
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var CliAssetDownloadServiceInterface
     */
    private $assetDownloadService;

    /**
     * @var RepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var RepositoryInterface
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
        CliAssetDownloadServiceInterface $cliAssetDownloadService,
        RepositoryInterface $migrationRunRepo,
        RepositoryInterface $mediaFileRepo
    ) {
        parent::__construct();

        $this->assetDownloadService = $cliAssetDownloadService;
        $this->migrationRunRepo = $migrationRunRepo;
        $this->mediaFileRepo = $mediaFileRepo;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MigrationAssetDownloadStartEvent::EVENT_NAME => 'onStart',
            MigrationAssetDownloadAdvanceEvent::EVENT_NAME => 'onAdvance',
            MigrationAssetDownloadFinishEvent::EVENT_NAME => 'onFinish',
        ];
    }

    public function onStart(MigrationAssetDownloadStartEvent $event): void
    {
        if ($this->io !== null) {
            $this->io->progressStart($event->getNumberOfFiles());
        }
    }

    public function onAdvance(MigrationAssetDownloadAdvanceEvent $event): void
    {
        if ($this->io !== null) {
            $this->io->progressAdvance();
        }
    }

    public function onFinish(MigrationAssetDownloadFinishEvent $event): void
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
            ->setName('migration:assets:download')
            ->setDescription('Downloads all assets')
            ->addOption('run-id', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('catalog-id', 'c', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->context = Context::createDefaultContext();

        $this->rundId = $input->getOption('run-id');
        if (!$this->rundId) {
            throw new InvalidArgumentException('No run-id provided');
        }

        $this->io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($output);

        $output->writeln('Downloading assets...');

        $this->assetDownloadService->setLogger($logger);
        $this->assetDownloadService->downloadAssets($this->rundId, $this->context);
        $this->finishRun();

        $output->writeln('Downloading done.');
    }

    private function finishRun(): void
    {
        if (!$this->hasUndownloadedMediaFiles()) {
            $this->migrationRunRepo->update([
                [
                    'id' => $this->rundId,
                    'status' => 'finished',
                ],
            ], $this->context);
        }
    }

    private function hasUndownloadedMediaFiles(): bool
    {
        $writtenCriteria = new Criteria();
        $writtenCriteria->addFilter(new EqualsFilter('runId', $this->rundId));
        $writtenCriteria->addFilter(new EqualsFilter('downloaded', false));

        return $this->mediaFileRepo->search($writtenCriteria, $this->context)->getTotal() > 0;
    }
}
