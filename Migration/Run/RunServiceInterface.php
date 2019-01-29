<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Service\ProgressState;

interface RunServiceInterface
{
    public function takeoverMigration(string $runUuid, Context $context): string;

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
}
