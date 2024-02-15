<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ThemeAssignMessage;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorServiceInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataConverterInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataFetcherInterface;
use SwagMigrationAssistant\Migration\Service\MigrationDataWriterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
#[Package('services-settings')]
class MigrationProcessHandler
{
    private int $batchSize = 100;

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     * @param EntityRepository<SwagMigrationDataCollection> $migrationDataRepo
     */
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly EntityRepository $migrationRunRepo,
        private readonly EntityRepository $migrationDataRepo,
        private readonly EntityRepository $migrationMediaFileRepo,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly MigrationDataFetcherInterface $migrationDataFetcher,
        private readonly MigrationDataConverterInterface $migrationDataConverter,
        private readonly MigrationDataWriterInterface $migrationDataWriter,
        private readonly MediaFileProcessorServiceInterface $mediaFileProcessorService,
        private readonly Connection $dbalConnection,
        private readonly TagAwareAdapterInterface $cache,
        private readonly EntityIndexerRegistry $indexer,
        private readonly RunService $runService
    ) {
    }

    public function __invoke(MigrationProcessMessage $message): void
    {
        $context = $message->getContext();
        $run = $this->getCurrentRun($message, $context);
        $progress = $run->getProgress();
        $migrationContext = $this->migrationContextFactory->create($run, $progress->getCurrentProgress(), $this->batchSize, $progress->getCurrentEntity());

        if ($migrationContext === null) {
            throw new \Exception('Migration context could not created.');
        }

        switch ($progress->getStep()) {
            case MigrationProgress::STATUS_FETCHING:
                $this->fetchData($migrationContext, $context, $progress);

                break;
            case MigrationProgress::STATUS_WRITING:
                $this->writeData($migrationContext, $context, $progress);

                break;
            case MigrationProgress::STATUS_MEDIA_PROCESSING:
                $this->processMedia($migrationContext, $context, $progress);

                break;
            case MigrationProgress::STATUS_ABORTING:
                $this->aborting($migrationContext, $context, $progress);

                break;
            case MigrationProgress::STATUS_CLEANUP:
                $this->cleanup($migrationContext, $context, $progress);

                break;
            case MigrationProgress::STATUS_INDEXING:
                $this->indexing($migrationContext, $context, $run);

                break;
        }
    }

    private function updateProgress(string $runId, MigrationProgress $progress, Context $context): void
    {
        $this->migrationRunRepo->update([[
            'id' => $runId,
            'progress' => $progress->jsonSerialize(),
        ]], $context);
    }

    private function getCurrentRun(MigrationProcessMessage $message, Context $context): SwagMigrationRunEntity
    {
        $run = $this->migrationRunRepo->search(new Criteria([$message->getRunUuid()]), $context)->getEntities()->first();

        if ($run === null) {
            throw new \Exception('No running migration found');
        }

        return $run;
    }

    private function getWriteTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('written', false));
        $criteria->addFilter(new EqualsFilter('convertFailure', false));
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->setLimit(1);

        $result = $this->migrationDataRepo->aggregate($criteria, $context);
        /** @var CountResult $countResult */
        $countResult = $result->get('count');

        return $countResult->getCount();
    }

    private function getMediaFileTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('written', true));
        $criteria->addFilter(new EqualsFilter('processed', false));
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->setLimit(1);

        $result = $this->migrationMediaFileRepo->aggregate($criteria, $context);
        /** @var CountResult $countResult */
        $countResult = $result->get('count');

        return $countResult->getCount();
    }

    private function fetchData(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
        $runId = $migrationContext->getRunUuid();
        $currentEntityTotal = $progress->getDataSets()[$progress->getCurrentEntity()];
        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);

        if (empty($data) || $currentEntityTotal <= $progress->getCurrentProgress()) {
            $this->changeProgressToNextEntity($progress, $context);
            $this->updateProgress($runId, $progress, $context);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $this->migrationDataConverter->convert($data, $migrationContext, $context);

        $progress->setCurrentProgress($progress->getCurrentProgress() + \count($data));
        $progress->setProgress($progress->getProgress() + \count($data));

        $this->updateProgress($runId, $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }

    private function writeData(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
        $writeTotal = $this->migrationDataWriter->writeData($migrationContext, $context);

        if ($writeTotal > 0) {
            $progress->setCurrentProgress($progress->getCurrentProgress() + $writeTotal);
            $progress->setProgress($progress->getProgress() + $writeTotal);

            $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $this->changeProgressToNextEntity($progress, $context);
        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }

    private function changeProgressToNextEntity(MigrationProgress $progress, Context $context): void
    {
        $dataSets = \array_keys($progress->getDataSets());
        $currentIndex = \array_search($progress->getCurrentEntity(), $dataSets, true);

        $nextEntity = null;
        if ($currentIndex !== false && $currentIndex < \count($dataSets) - 1) {
            $nextEntity = $dataSets[$currentIndex + 1];
        }

        if ($nextEntity === null && $progress->getStep() === MigrationProgress::STATUS_FETCHING) {
            $nextEntity = \current($dataSets);
            $progress->setStep(MigrationProgress::STATUS_WRITING);
            $progress->setProgress(0);
            $progress->setTotal($this->getWriteTotal($context));
        } elseif ($nextEntity === null && $progress->getStep() === MigrationProgress::STATUS_WRITING) {
            $progress->setStep(MigrationProgress::STATUS_MEDIA_PROCESSING);
            $nextEntity = DefaultEntities::MEDIA;
            $progress->setProgress(0);
            $progress->setTotal($this->getMediaFileTotal($context));
        }

        $progress->setCurrentProgress(0);
        $progress->setCurrentEntity($nextEntity);
    }

    private function processMedia(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
        $fileCount = $this->mediaFileProcessorService->processMediaFiles($migrationContext, $context);

        if ($fileCount <= 0) {
            $progress->setStep(MigrationProgress::STATUS_CLEANUP);
            $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $progress->setCurrentProgress($progress->getCurrentProgress() + $fileCount);
        $progress->setProgress($progress->getProgress() + $fileCount);
        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }

    private function cleanup(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
        $deleteCount = $this->removeMigrationData();

        if ($deleteCount <= 0) {
            $progress->setStep(MigrationProgress::STATUS_INDEXING);
        }

        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }

    private function indexing(MigrationContextInterface $migrationContext, Context $context, SwagMigrationRunEntity $run): void
    {
        $this->cache->clear();
        $this->indexer->index(true);
        $this->assignThemes($run, $context);

        $progress = $run->getProgress();

        if ($run->getStatus() === SwagMigrationRunEntity::STATUS_ABORTED) {
            $progress->setStep(MigrationProgress::STATUS_ABORTED);
        } else {
            $progress->setStep(MigrationProgress::STATUS_WAITING_FOR_APPROVE);
        }

        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
    }

    private function aborting(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
        $this->runService->cleanupMappingChecksums($migrationContext->getConnection()->getId(), $context, false);

        $progress->setStep(MigrationProgress::STATUS_CLEANUP);
        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }

    private function removeMigrationData(): int
    {
        return (new QueryBuilder($this->dbalConnection))
            ->delete(SwagMigrationDataDefinition::ENTITY_NAME)
            ->andWhere('written = 1')
            ->setMaxResults(1000)
            ->executeStatement();
    }

    private function assignThemes(SwagMigrationRunEntity $run, Context $context): void
    {
        $this->bus->dispatch(new ThemeAssignMessage($context, $run->getId()));
    }
}
