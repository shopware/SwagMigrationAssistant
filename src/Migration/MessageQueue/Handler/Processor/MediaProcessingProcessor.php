<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler\Processor;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\FetchDataSetMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\FetchProcessorMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\RunExceptionLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('fundamentals@after-sales')]
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
        private readonly MessageBusInterface $bus,
        private readonly LoggingService $loggingService,
        private readonly Connection $dbalConnection,
        private readonly MediaFileProcessorRegistryInterface $mediaFileProcessorRegistry,
        private readonly DataSetRegistry $dataSetRegistry,
        private readonly MigrationConfiguration $migrationConfig,
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
        MigrationProgress $progress,
    ): void {
        $connection = $run->getConnection();
        if ($connection === null) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $migrationContext->getRunUuid());
        }

        $mediaFiles = $this->getMediaFiles($migrationContext);

        if ($mediaFiles === []) {
            $this->runTransitionService->transitionToRunStep($migrationContext->getRunUuid(), MigrationStep::CLEANUP);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $currentDataSet = null;
        $currentCount = 0;
        $workload = [];
        $skipped = [];
        foreach ($mediaFiles as $mediaFile) {
            if ($currentDataSet === null) {
                try {
                    $currentDataSet = $this->dataSetRegistry->getDataSet($migrationContext, $mediaFile['entity']);
                    $migrationContext->setDataSet($currentDataSet);
                } catch (MigrationException $e) {
                    if ($e->getErrorCode() === MigrationException::DATASET_NOT_FOUND) {
                        $this->loggingService->log(
                            MigrationLogBuilder::fromMigrationContext($migrationContext)
                                ->withEntityName($mediaFile['entity'])
                                ->withEntityId($mediaFile['id'])
                                ->build(FetchDataSetMissingLog::class)
                        );

                        $skipped[] = [
                            'id' => $mediaFile['id'],
                            'processFailure' => true,
                        ];

                        continue;
                    }

                    throw $e;
                }
            }

            if ($currentDataSet::getEntity() !== $mediaFile['entity']) {
                break;
            }

            ++$currentCount;

            if ($currentCount > $this->migrationConfig->migrationMediaProcessingBatchSize) {
                break;
            }

            $workload[] = new MediaProcessWorkloadStruct(
                $mediaFile['media_id'],
                $run->getId(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE
            );
        }

        if ($skipped !== []) {
            $this->migrationMediaFileRepo->update($skipped, $context);
        }

        $skippedCount = \count($skipped);

        if ($currentDataSet === null || $workload === []) {
            $this->finalizeProcessStep(
                $context,
                $migrationContext,
                $run,
                $progress,
                $skippedCount
            );

            return;
        }

        try {
            $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);
            $workload = $processor->process($migrationContext, $context, $workload);
            $workload = $this->processFailures($context, $migrationContext, $processor, $workload);
        } catch (MigrationException $e) {
            if ($e->getErrorCode() === MigrationException::NO_CONNECTION_FOUND) {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($migrationContext)
                        ->withException($e)
                        ->withEntityName($currentDataSet::getEntity())
                        ->build(FetchProcessorMissingLog::class)
                );
            } else {
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withException($e)
                    ->withEntityName($currentDataSet::getEntity())
                    ->build(RunExceptionLog::class)
            );
        }

        $this->markUnfinishedWorkloadAsFailed($mediaFiles, $workload, $context);

        $workloadCount = \count(\array_filter(
            $workload,
            static fn (MediaProcessWorkloadStruct $item): bool => \in_array(
                $item->getState(),
                [MediaProcessWorkloadStruct::FINISH_STATE, MediaProcessWorkloadStruct::ERROR_STATE],
                true
            )
        ));
        $this->finalizeProcessStep(
            $context,
            $migrationContext,
            $run,
            $progress,
            $workloadCount + $skippedCount
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMediaFiles(MigrationContextInterface $migrationContext): array
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder();

        $result = $queryBuilder
            ->select('*')
            ->from('swag_migration_media_file')
            ->where('run_id = :runId')
            ->andWhere('written = 1')
            ->andWhere('processed = 0')
            ->andWhere('process_failure = 0')
            ->orderBy('id, file_size, entity')
            ->setMaxResults($migrationContext->getLimit())
            ->setParameter('runId', Uuid::fromHexToBytes($migrationContext->getRunUuid()))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($result as &$media) {
            $media['id'] = Uuid::fromBytesToHex($media['id']);
            $media['run_id'] = Uuid::fromBytesToHex($media['run_id']);
            $media['media_id'] = Uuid::fromBytesToHex($media['media_id']);
        }

        return $result;
    }

    /**
     * @param MediaProcessWorkloadStruct[] $workload
     *
     * @return MediaProcessWorkloadStruct[]
     */
    private function processFailures(
        Context $context,
        MigrationContextInterface $migrationContext,
        MediaFileProcessorInterface $processor,
        array $workload,
    ): array {
        $mappedWorkload = [];
        foreach ($workload as $item) {
            $mappedWorkload[$item->getMediaId()] = $item;
        }

        for ($i = 0; $i < $this->migrationConfig->migrationDefaultExceptionThreshold; ++$i) {
            $errorWorkload = [];

            foreach ($mappedWorkload as $item) {
                if ($item->getErrorCount() > 0 && $item->getState() !== MediaProcessWorkloadStruct::ERROR_STATE) {
                    $errorWorkload[] = $item;
                }
            }

            if ($errorWorkload === []) {
                break;
            }

            $retriedWorkload = $processor->process($migrationContext, $context, $errorWorkload);
            foreach ($retriedWorkload as $item) {
                $mappedWorkload[$item->getMediaId()] = $item;
            }
        }

        return \array_values($mappedWorkload);
    }

    /**
     * @param array<int, array<string, mixed>> $mediaFiles
     * @param MediaProcessWorkloadStruct[] $workload
     */
    private function markUnfinishedWorkloadAsFailed(array $mediaFiles, array $workload, Context $context): void
    {
        $mediaFileIds = [];
        foreach ($mediaFiles as $mediaFile) {
            $mediaFileIds[$mediaFile['media_id']] = $mediaFile['id'];
        }

        $failedMediaFiles = [];
        foreach ($workload as $item) {
            if (\in_array(
                $item->getState(),
                [MediaProcessWorkloadStruct::FINISH_STATE, MediaProcessWorkloadStruct::ERROR_STATE],
                true
            )) {
                continue;
            }

            $item->setState(MediaProcessWorkloadStruct::ERROR_STATE);
            $mediaFileId = $mediaFileIds[$item->getMediaId()] ?? null;
            if ($mediaFileId !== null) {
                $failedMediaFiles[$mediaFileId] = [
                    'id' => $mediaFileId,
                    'processFailure' => true,
                ];
            }
        }

        if ($failedMediaFiles !== []) {
            $this->migrationMediaFileRepo->update(\array_values($failedMediaFiles), $context);
        }
    }

    private function isAllMediaProcessed(Context $context, string $runId): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('runId', $runId)
        );
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('processed', false),
                    new EqualsFilter('processFailure', false),
                ]
            )
        );

        $unprocessedCount = $this->migrationMediaFileRepo->search($criteria, $context)->getTotal();

        return $unprocessedCount === 0;
    }

    private function finalizeProcessStep(
        Context $context,
        MigrationContextInterface $migrationContext,
        SwagMigrationRunEntity $run,
        MigrationProgress $progress,
        int $itemCount,
    ): void {
        $progress->setCurrentEntityProgress($progress->getCurrentEntityProgress() + $itemCount);
        $progress->setProgress($progress->getProgress() + $itemCount);
        $this->updateProgress($run->getId(), $progress, $context);

        if ($this->isAllMediaProcessed($context, $migrationContext->getRunUuid())) {
            $this->runTransitionService->transitionToRunStep(
                $migrationContext->getRunUuid(),
                MigrationStep::CLEANUP
            );
        }

        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
    }
}
