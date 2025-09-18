<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MessageQueue\Message\AbortCompletionMessage;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ResetChecksumMessage;
use SwagMigrationAssistant\Migration\Run\MigrationProgress;
use SwagMigrationAssistant\Migration\Run\ProgressDataSetCollection;
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
    ) {
    }

    public function __invoke(ResetChecksumMessage $message): void
    {
        $connectionId = $message->getConnectionId();
        $totalMappings = $message->getTotalMappings();
        $progress = null;

        if ($totalMappings === null) {
            $totalMappings = $this->getTotalMappingsCount($connectionId, $message->isResettingAll());

            if ($message->getRunId() !== null && $totalMappings > 0) {
                $progress = $this->initializeProgress(
                    $message,
                    $totalMappings,
                    $message->getContext()
                );
            }
        }

        $query = $this->connection->createQueryBuilder()
            ->select('m.id')
            ->from('swag_migration_mapping', 'm')
            ->where('m.checksum IS NOT NULL')
            ->andWhere('m.connection_id = :connectionId')
            ->setParameter('connectionId', Uuid::fromHexToBytes($connectionId))
            ->setMaxResults(self::BATCH_SIZE);

        if (!$message->isResettingAll()) {
            $query->innerJoin(
                'm',
                'swag_migration_data',
                'd',
                'd.mapping_uuid = m.id AND d.written = 0'
            );
        }

        $ids = $query->executeQuery()->fetchFirstColumn();
        $batchSize = \count($ids);

        if ($batchSize === 0) {
            $this->clearResettingChecksumsFlag();
            $this->dispatchCompletionMessage(
                $message,
                $progress
            );

            return;
        }

        $this->connection->createQueryBuilder()
            ->update('swag_migration_mapping')
            ->set('checksum', 'NULL')
            ->where('id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::BINARY)
            ->executeStatement();

        $newProcessedCount = $message->getProcessedMappings() + $batchSize;

        if ($message->getRunId() !== null) {
            $progress = $this->updateProgress(
                $message,
                $newProcessedCount,
                $totalMappings,
                $message->getContext()
            );
        }

        if ($batchSize < self::BATCH_SIZE) {
            $this->clearResettingChecksumsFlag();
            $this->dispatchCompletionMessage(
                $message,
                $progress,
            );

            return;
        }

        $this->messageBus->dispatch(new ResetChecksumMessage(
            $message->getConnectionId(),
            $message->getContext(),
            $message->isResettingAll(),
            $message->getRunId(),
            $message->getEntity(),
            $totalMappings,
            $newProcessedCount
        ));
    }

    private function dispatchCompletionMessage(ResetChecksumMessage $message, ?MigrationProgress $progress): void
    {
        if ($message->getRunId() === null) {
            return;
        }

        $finalProgress = new MigrationProgress(
            0,
            0,
            $progress?->getDataSets() ?? new ProgressDataSetCollection(),
            $message->getEntity() ?? DefaultEntities::RULE,
            $progress?->getCurrentEntityProgress() ?? 0
        );

        $completion = new AbortCompletionMessage(
            $message->getRunId(),
            $finalProgress,
            $message->getContext()
        );

        $this->messageBus->dispatch($completion);
    }

    private function getTotalMappingsCount(string $connectionId, bool $resetAll): int
    {
        $query = $this->connection->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from('swag_migration_mapping', 'm')
            ->where('m.checksum IS NOT NULL')
            ->andWhere('m.connection_id = :connectionId')
            ->setParameter('connectionId', Uuid::fromHexToBytes($connectionId));

        if (!$resetAll) {
            $query->innerJoin(
                'm',
                'swag_migration_data',
                'd',
                'd.mapping_uuid = m.id AND d.written = 0'
            );
        }

        return (int) $query->executeQuery()->fetchOne();
    }

    private function initializeProgress(ResetChecksumMessage $message, int $total, Context $context): MigrationProgress
    {
        $progress = new MigrationProgress(
            0,
            $total,
            new ProgressDataSetCollection(),
            $message->getEntity() ?? DefaultEntities::RULE,
            0
        );

        $this->migrationRunRepo->update([[
            'id' => $message->getRunId(),
            'progress' => $progress->jsonSerialize(),
        ]], $context);

        return $progress;
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
