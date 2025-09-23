<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Exception\NoConnectionFoundException;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ProcessorNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('fundamentals@after-sales')]
final class ProcessMediaHandler
{
    final public const MEDIA_ERROR_THRESHOLD = 3;

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private readonly EntityRepository $migrationRunRepo,
        private readonly MediaFileProcessorRegistryInterface $mediaFileProcessorRegistry,
        private readonly LoggingServiceInterface $loggingService,
        private readonly MigrationContextFactoryInterface $migrationContextFactory,
        private readonly MessageBusInterface $messageBus,
        private readonly RunTransitionServiceInterface $runTransitionService,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @throws MigrationException
     */
    public function __invoke(ProcessMediaMessage $message): void
    {
        $context = $message->getContext();

        $run = $this->migrationRunRepo->search(new Criteria([$message->getRunId()]), $context)->first();

        if (!$run instanceof SwagMigrationRunEntity) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $message->getRunId());
        }

        $connection = $run->getConnection();
        if ($connection === null) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $message->getRunId());
        }

        $migrationContext = $this->migrationContextFactory->create($run, 0, 0, $message->getEntityName());

        if ($migrationContext === null) {
            throw MigrationException::entityNotExists(SwagMigrationRunEntity::class, $message->getRunId());
        }

        $workload = [];
        foreach ($message->getMediaFileIds() as $mediaFileId) {
            $workload[] = new MediaProcessWorkloadStruct(
                $mediaFileId,
                $message->getRunId(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE
            );
        }

        try {
            $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);
            $workload = $processor->process($migrationContext, $context, $workload);
            $this->processFailures($context, $migrationContext, $processor, $workload);
        } catch (NoConnectionFoundException $e) {
            $this->loggingService->addLogEntry(new ProcessorNotFoundLog(
                $message->getRunId(),
                $message->getEntityName(),
                $connection->getProfileName(),
                $connection->getGatewayName()
            ));

            $this->loggingService->saveLogging($context);
        } catch (\Exception $e) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $message->getRunId(),
                $message->getEntityName(),
                $e
            ));

            $this->loggingService->saveLogging($context);
        }

        $this->updateProgress($message, $run->getProgress(), $context);

        $this->transitionStepIfReady($message, $migrationContext, $context);
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

    private function updateProgress(
        ProcessMediaMessage $message,
        MigrationProgress $progress,
        Context $context
    ): void {
        $progress->setCurrentEntityProgress($progress->getCurrentEntityProgress() + \count($message->getMediaFileIds()));
        $progress->setProgress($progress->getProgress() + \count($message->getMediaFileIds()));

        $this->migrationRunRepo->update([[
            'id' => $message->getRunId(),
            'progress' => $progress->jsonSerialize(),
        ]], $context);
    }

    private function transitionStepIfReady(
        ProcessMediaMessage $message,
        MigrationContext $migrationContext,
        Context $context
    ): void
    {
        $this->connection->transactional(function() use ($message, $migrationContext, $context) {
            // Lock the row to prevent race conditions
            $this->connection->fetchAssociative(
                'SELECT all_media_processed FROM swag_migration_run WHERE id = :runId FOR UPDATE',
                ['runId' => Uuid::fromHexToBytes($message->getRunId())]
            );

            $unprocessedMediaCount = (int) $this->connection->fetchOne(
                'SELECT COUNT(id) FROM swag_migration_media_file WHERE processed = 0 AND process_failure = 0 AND run_id = :runId',
                ['runId' => Uuid::fromHexToBytes($message->getRunId())]
            );

            $affectedRows = 0;
            if ($unprocessedMediaCount === 0) {
                $affectedRows = $this->connection->executeStatement(
                    'UPDATE swag_migration_run SET all_media_processed = TRUE WHERE id = :runId',
                    ['runId' => Uuid::fromHexToBytes($message->getRunId())]
                );
            }

            // Only transition if we updated the flag in this transaction to prevent duplicate messages
            if ($affectedRows > 0) {
                $this->runTransitionService->transitionToRunStep($migrationContext->getRunUuid(), MigrationStep::CLEANUP);
                $this->messageBus->dispatch(new MigrationProcessMessage($context, $migrationContext->getRunUuid()));
            }
        });
    }
}
