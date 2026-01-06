<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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

        if ($totalMappings === null) {
            $totalMappings = $this->getTotalMappingsCount($connectionId);
        }

        $affectedRows = $this->resetChecksums($connectionId);
        $newProcessedCount = $message->getProcessedMappings() + $affectedRows;

        $isCompleted = $affectedRows < self::BATCH_SIZE;

        if ($isCompleted) {
            $this->handleCompletion($message);

            if ($message->isPartOfAbort()) {
                return;
            }
        }

        if ($message->getRunId() !== null && $totalMappings > 0) {
            $this->updateProgress($message, $newProcessedCount, $totalMappings);
        }

        if (!$isCompleted) {
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
    }

    private function handleCompletion(ResetChecksumMessage $message): void
    {
        $this->clearResettingChecksumsFlag();
        $runId = $message->getRunId();

        if (!$message->isPartOfAbort() || $runId === null) {
            return;
        }

        $this->runTransitionService->forceTransitionToRunStep(
            $runId,
            MigrationStep::CLEANUP
        );

        $this->updateProgress($message, 0, 0, true);

        $this->messageBus->dispatch(new MigrationProcessMessage(
            $message->getContext(),
            $runId,
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

    private function updateProgress(ResetChecksumMessage $message, int $processed, int $total, bool $isAborted = false): void
    {
        $runId = $message->getRunId();

        if ($runId === null) {
            return;
        }

        $run = $this->migrationRunRepo->search(
            new Criteria([$runId]),
            $message->getContext(),
        )->getEntities()->first();

        if ($run === null) {
            return;
        }

        $progress = $run->getProgress();
        $newProgress = new MigrationProgress(
            $processed,
            $total,
            $progress?->getDataSets() ?? new ProgressDataSetCollection(),
            $message->getEntity() ?? DefaultEntities::RULE,
            $processed
        );

        if ($isAborted) {
            $newProgress->setIsAborted(true);
        }

        $this->migrationRunRepo->update([[
            'id' => $runId,
            'progress' => $newProgress->jsonSerialize(),
        ]], $message->getContext());
    }

    private function clearResettingChecksumsFlag(): void
    {
        $this->connection->executeStatement(
            'UPDATE swag_migration_general_setting SET `is_resetting_checksums` = 0;'
        );
    }
}
