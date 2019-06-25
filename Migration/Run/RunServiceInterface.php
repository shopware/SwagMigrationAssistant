<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Service\ProgressState;

interface RunServiceInterface
{
    public function takeoverMigration(string $runUuid, Context $context): string;

    public function abortMigration(string $runUuid, Context $context): void;

    /**
     * @param int[] $dataSelectionIds
     */
    public function createMigrationRun(
        string $connectionId,
        array $dataSelectionIds,
        Context $context
    ): ?ProgressState;

    public function calculateWriteProgress(SwagMigrationRunEntity $run, Context $context): array;

    public function calculateMediaFilesProgress(SwagMigrationRunEntity $run, Context $context): array;

    public function calculateCurrentTotals(string $runId, bool $isWritten, Context $context): array;

    public function updateConnectionCredentials(Context $context, string $connectionUuid, array $credentialFields): void;

    public function finishMigration(string $runUuid, Context $context): void;
}
