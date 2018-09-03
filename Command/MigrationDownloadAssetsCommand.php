<?php declare(strict_types=1);

namespace SwagMigrationNext\Command;

use InvalidArgumentException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadAdvanceEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadFinishEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadStartEvent;
use SwagMigrationNext\Migration\AssetDownloadService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MigrationDownloadAssetsCommand extends Command implements EventSubscriberInterface
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var RepositoryInterface
     */
    private $migrationMappingRepository;

    /**
     * @var FileSaver
     */
    private $fileSaver;

    /**
     * @var EventDispatcherInterface
     */
    private $event;

    public function __construct(
        RepositoryInterface $migrationMappingRepository,
        FileSaver $fileSaver,
        EventDispatcherInterface $event
    ) {
        parent::__construct();

        $this->migrationMappingRepository = $migrationMappingRepository;
        $this->fileSaver = $fileSaver;
        $this->event = $event;
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
            ->addOption('tenant-id', 't', InputOption::VALUE_REQUIRED)
            ->addOption('catalog-id', 'c', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tenantId = $input->getOption('tenant-id');

        if (!$tenantId) {
            throw new InvalidArgumentException('No tenant id provided');
        }
        if (!Uuid::isValid($tenantId)) {
            throw new InvalidArgumentException('Invalid uuid provided');
        }
        $context = Context::createDefaultContext($tenantId);

        $this->io = new SymfonyStyle($input, $output);
        $logger = new ConsoleLogger($output);

        $output->writeln('Downloading assets...');

        $assetDownloadService = new AssetDownloadService(
            $this->migrationMappingRepository,
            $this->fileSaver,
            $this->event,
            $logger
        );
        $assetDownloadService->downloadAssets($context);

        $output->writeln('Downloading done.');
    }
}
