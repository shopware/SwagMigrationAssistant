<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('services-settings')]
class MediaProcessingProcessor extends AbstractProcessor
{
    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     * @param EntityRepository<SwagMigrationDataCollection> $migrationDataRepo
     * @param EntityRepository<SwagMigrationMediaFileCollection> $migrationMediaFileRepo
     */
    public function __construct(
        EntityRepository $migrationRunRepo,
        EntityRepository $migrationDataRepo,
        EntityRepository $migrationMediaFileRepo,
        RunTransitionServiceInterface $runTransitionService,
        private readonly MediaFileProcessorServiceInterface $mediaFileProcessorService,
        private readonly MessageBusInterface $bus
    ) {
        parent::__construct(
            $migrationRunRepo,
            $migrationDataRepo,
            $migrationMediaFileRepo,
            $runTransitionService
        );
    }

    public function supports(MigrationStep $step): bool
    {
        return $step === MigrationStep::MEDIA_PROCESSING;
    }

    public function process(
        MigrationContextInterface $migrationContext,
        Context $context,
        SwagMigrationRunEntity $run,
        MigrationProgress $progress
    ): void {
        $fileCount = $this->mediaFileProcessorService->processMediaFiles($migrationContext, $context);

        if ($fileCount <= 0) {
            $this->runTransitionService->transitionToRunStep($migrationContext->getRunUuid(), MigrationStep::CLEANUP);
            $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $progress->setCurrentEntityProgress($progress->getCurrentEntityProgress() + $fileCount);
        $progress->setProgress($progress->getProgress() + $fileCount);
        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }
}
