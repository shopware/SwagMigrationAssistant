<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MessageQueue\Message\MigrationProcessMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ResetChecksumMessage;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunCollection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('fundamentals@after-sales')]
final readonly class ResetChecksumHandler
{
    public const BATCH_SIZE = 250;

    /**
     * @param EntityRepository<SwagMigrationRunCollection> $migrationRunRepo
     */
    public function __construct(
        private Connection $connection,
        private MessageBusInterface $messageBus,
        private EntityRepository $migrationRunRepo,
        private RunTransitionServiceInterface $runTransitionService,
    ) {
    }

    public function __invoke(ResetChecksumMessage $message): void
    {
        $connectionId = $message->getConnectionId();
        $totalMappings = $message->getTotalMappings();
        $progress = null;

        if ($totalMappings === null) {
            $totalMappings = $this->getTotalMappingsCount($connectionId);

            if ($message->getRunId() !== null && $totalMappings > 0) {
                $progress = $this->updateProgress(
                    $message,
                    0,
                    $totalMappings,
                    $message->getContext()
                );
            }
        }

        $affectedRows = $this->resetChecksums($connectionId);

        if ($affectedRows === 0) {
            $this->handleCompletion($message, $progress);

            return;
        }

        $newProcessedCount = $message->getProcessedMappings() + $affectedRows;

        if ($message->getRunId() !== null) {
            $progress = $this->updateProgress(
                $message,
                $newProcessedCount,
                $totalMappings,
                $message->getContext()
            );
        }

        if ($affectedRows < self::BATCH_SIZE) {
            $this->handleCompletion($message, $progress);

            return;
        }

        $this->messageBus->dispatch(new ResetChecksumMessage(
            $message->getConnectionId(),
            $message->getContext(),
            $message->getRunId(),
            $message->getEntity(),
            $totalMappings,
            $newProcessedCount,
            $message->isPartOfAbort()
        ));
    }

    private function handleCompletion(ResetChecksumMessage $message, ?MigrationProgress $progress): void
    {
        $this->clearResettingChecksumsFlag();

        if (!$message->isPartOfAbort() || $message->getRunId() === null) {
            return;
        }

        $runId = $message->getRunId();
        $context = $message->getContext();

        $this->runTransitionService->forceTransitionToRunStep(
            $runId,
            MigrationStep::CLEANUP
        );

        $finalProgress = new MigrationProgress(
            0,
            0,
            $progress?->getDataSets() ?? new ProgressDataSetCollection(),
            $message->getEntity() ?? DefaultEntities::RULE,
            $progress?->getCurrentEntityProgress() ?? 0
        );
        $finalProgress->setIsAborted(true);

        $this->migrationRunRepo->upsert([
            [
                'id' => $runId,
                'progress' => $finalProgress->jsonSerialize(),
            ],
        ], $context);

        $this->messageBus->dispatch(new MigrationProcessMessage(
            $context,
            $runId
        ));
    }

    private function resetChecksums(string $connectionId): int
    {
        return (int) $this->connection->executeStatement(
            'UPDATE swag_migration_mapping
            SET checksum = NULL
            WHERE checksum IS NOT NULL
              AND connection_id = :connectionId
            LIMIT :limit',
            [
                'connectionId' => Uuid::fromHexToBytes($connectionId),
                'limit' => self::BATCH_SIZE,
            ],
            [
                'connectionId' => ParameterType::BINARY,
                'limit' => ParameterType::INTEGER,
            ]
        );
    }

    private function getTotalMappingsCount(string $connectionId): int
    {
        return (int) $this->connection->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from('swag_migration_mapping', 'm')
            ->where('m.checksum IS NOT NULL')
            ->andWhere('m.connection_id = :connectionId')
            ->setParameter('connectionId', Uuid::fromHexToBytes($connectionId))
            ->executeQuery()
            ->fetchOne();
    }

    private function updateProgress(ResetChecksumMessage $message, int $processed, int $total, Context $context): MigrationProgress
    {
        $progress = new MigrationProgress(
            $processed,
            $total,
            new ProgressDataSetCollection(),
            $message->getEntity() ?? DefaultEntities::RULE,
            $processed
        );

        $this->migrationRunRepo->update([[
            'id' => $message->getRunId(),
            'progress' => $progress->jsonSerialize(),
        ]], $context);

        return $progress;
    }

    private function clearResettingChecksumsFlag(): void
    {
        $this->connection->executeStatement(
            'UPDATE swag_migration_general_setting SET `is_resetting_checksums` = 0;'
        );
    }
}
