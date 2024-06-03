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
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataDefinition;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ThemeAssignMessage;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunService;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
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
/**
 * @internal
 */
final class MigrationProcessHandler
{
    private int $batchSize = 100;

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     * @param EntityRepository<SwagMigrationDataCollection> $migrationDataRepo
     * @param EntityRepository<SwagMigrationDataCollection> $migrationMediaFileRepo
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
        private readonly RunService $runService,
        private readonly RunTransitionServiceInterface $runTransitionService,
    ) {
    }

    public function __invoke(MigrationProcessMessage $message): void
    {
        $context = $message->getContext();
        $run = $this->getCurrentRun($message, $context);
        $progress = $run->getProgress();

        if ($progress === null) {
            throw MigrationException::noRunProgressFound($run->getId());
        }

        $migrationContext = $this->migrationContextFactory->create($run, $progress->getCurrentEntityProgress(), $this->batchSize, $progress->getCurrentEntity());

        if ($migrationContext === null) {
            throw MigrationException::migrationContextNotCreated();
        }

        match ($run->getStep()) {
            MigrationStep::FETCHING => $this->fetchData($migrationContext, $context, $progress, $run),
            MigrationStep::WRITING => $this->writeData($migrationContext, $context, $progress, $run),
            MigrationStep::MEDIA_PROCESSING => $this->processMedia($migrationContext, $context, $progress),
            MigrationStep::ABORTING => $this->aborting($migrationContext, $context, $progress),
            MigrationStep::CLEANUP => $this->cleanup($migrationContext, $context, $progress),
            MigrationStep::INDEXING => $this->indexing($migrationContext, $context, $run),
            default => throw MigrationException::unknownProgressStep($run->getStepValue()),
        };
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
            throw MigrationException::noRunningMigration($message->getRunUuid());
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
        $countResult = $result->get('count');

        if (!$countResult instanceof CountResult) {
            return 0;
        }

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
        $countResult = $result->get('count');

        if (!$countResult instanceof CountResult) {
            return 0;
        }

        return $countResult->getCount();
    }

    private function fetchData(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress, SwagMigrationRunEntity $run): void
    {
        $runId = $migrationContext->getRunUuid();
        $currentEntityTotal = $progress->getDataSets()->getTotalByEntityName($progress->getCurrentEntity());
        $data = $this->migrationDataFetcher->fetchData($migrationContext, $context);

        if (empty($data) || $currentEntityTotal <= $progress->getCurrentEntityProgress()) {
            $this->changeProgressToNextEntity($run, $progress, $context);
            $this->updateProgress($runId, $progress, $context);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $this->migrationDataConverter->convert($data, $migrationContext, $context);

        $progress->setCurrentEntityProgress($progress->getCurrentEntityProgress() + \count($data));
        $progress->setProgress($progress->getProgress() + \count($data));

        $this->updateProgress($runId, $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }

    private function writeData(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress, SwagMigrationRunEntity $run): void
    {
        $writeTotal = $this->migrationDataWriter->writeData($migrationContext, $context);

        if ($writeTotal > 0) {
            $progress->setCurrentEntityProgress($progress->getCurrentEntityProgress() + $writeTotal);
            $progress->setProgress($progress->getProgress() + $writeTotal);

            $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $this->changeProgressToNextEntity($run, $progress, $context);
        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }

    private function changeProgressToNextEntity(SwagMigrationRunEntity $run, MigrationProgress $progress, Context $context): void
    {
        $dataSets = \array_keys($progress->getDataSets()->getEntityNames());
        $currentIndex = \array_search($progress->getCurrentEntity(), $dataSets, true);

        $nextEntity = null;
        if ($currentIndex !== false && $currentIndex < \count($dataSets) - 1) {
            $nextEntity = $dataSets[$currentIndex + 1];
        }

        if ($nextEntity === null && $run->getStep() === MigrationStep::FETCHING) {
            $nextEntity = \current($dataSets);
            $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::WRITING);
            $progress->setProgress(0);
            $progress->setTotal($this->getWriteTotal($context));
        } elseif ($nextEntity === null && $run->getStep() === MigrationStep::WRITING) {
            $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::MEDIA_PROCESSING);
            $nextEntity = DefaultEntities::MEDIA;
            $progress->setProgress(0);
            $progress->setTotal($this->getMediaFileTotal($context));
        }

        $progress->setCurrentEntityProgress(0);
        $progress->setCurrentEntity((string) $nextEntity);
    }

    private function processMedia(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
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

    private function cleanup(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
        $deleteCount = $this->removeMigrationData();

        if ($deleteCount <= 0) {
            $this->runTransitionService->transitionToRunStep($migrationContext->getRunUuid(), MigrationStep::INDEXING);
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

        if ($progress === null) {
            throw MigrationException::noRunProgressFound($run->getId());
        }

        if ($progress->isAborted()) {
            $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::ABORTED);
        } else {
            $this->runTransitionService->transitionToRunStep($run->getId(), MigrationStep::WAITING_FOR_APPROVE);
        }

        $this->updateProgress($migrationContext->getRunUuid(), $progress, $context);
    }

    private function aborting(MigrationContextInterface $migrationContext, Context $context, MigrationProgress $progress): void
    {
        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            throw MigrationException::noConnectionFound();
        }

        $this->runService->cleanupMappingChecksums($connection->getId(), $context, false);

        $this->runTransitionService->forceTransitionToRunStep($migrationContext->getRunUuid(), MigrationStep::CLEANUP);
        $progress->setIsAborted(true);
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
