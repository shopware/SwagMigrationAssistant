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
    final public const MEDIA_ERROR_THRESHOLD = 3;
    final public const MESSAGE_SIZE = 10;

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
        if (empty($mediaFiles)) {
            $this->runTransitionService->transitionToRunStep($migrationContext->getRunUuid(), MigrationStep::CLEANUP);
            $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));

            return;
        }

        $currentDataSet = null;
        $currentCount = 0;
        $workload = [];
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
                        continue;
                    }

                    throw $e;
                }
            }

            if ($currentDataSet::getEntity() !== $mediaFile['entity']) {
                break;
            }

            ++$currentCount;

            if ($currentCount > self::MESSAGE_SIZE) {
                break;
            }

            $workload[] = new MediaProcessWorkloadStruct(
                $mediaFile['media_id'],
                $run->getId(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE
            );
        }

        \assert($currentDataSet !== null);

        try {
            $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);
            $workload = $processor->process($migrationContext, $context, $workload);
            $this->processFailures($context, $migrationContext, $processor, $workload);
        } catch (MigrationException $e) {
            if ($e->getErrorCode() === MigrationException::NO_CONNECTION_FOUND) {
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($migrationContext)
                        ->withExceptionMessage($e->getMessage())
                        ->withExceptionTrace($e->getTrace())
                        ->withEntityName($currentDataSet::getEntity())
                        ->build(FetchProcessorMissingLog::class)
                );
            } else {
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withExceptionMessage($e->getMessage())
                    ->withExceptionTrace($e->getTrace())
                    ->withEntityName($currentDataSet::getEntity())
                    ->build(RunExceptionLog::class)
            );
        }

        $progress->setCurrentEntityProgress($progress->getCurrentEntityProgress() + \count($workload));
        $progress->setProgress($progress->getProgress() + \count($workload));
        $this->updateProgress($run->getId(), $progress, $context);

        if ($this->isAllMediaProcessed($context, $migrationContext->getRunUuid())) {
            $this->runTransitionService->transitionToRunStep($migrationContext->getRunUuid(), MigrationStep::CLEANUP);
        }

        $this->bus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
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
            ->orderBy('entity, file_size')
            ->setFirstResult($migrationContext->getOffset())
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
     */
    private function processFailures(
        Context $context,
        MigrationContextInterface $migrationContext,
        MediaFileProcessorInterface $processor,
        array $workload,
    ): void {
        for ($i = 0; $i < self::MEDIA_ERROR_THRESHOLD; ++$i) {
            $errorWorkload = [];

            foreach ($workload as $item) {
                if ($item->getErrorCount() > 0) {
                    $errorWorkload[] = $item;
                }
            }

            if (empty($errorWorkload)) {
                break;
            }

            $workload = $processor->process($migrationContext, $context, $errorWorkload);
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
}
