<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\ProgressState;

interface RunServiceInterface
{
    public function takeoverMigration(string $runUuid, Context $context): string;

    public function abortMigration(string $runUuid, Context $context): void;

    public function cleanupMappingChecksums(string $connectionUuid, Context $context): void;

    /**
     * @param string[] $dataSelectionIds
     */
    public function createMigrationRun(
        MigrationContextInterface $migrationContext,
        array $dataSelectionIds,
        Context $context
    ): ?ProgressState;

    public function calculateWriteProgress(SwagMigrationRunEntity $run, Context $context): array;

    public function calculateMediaFilesProgress(SwagMigrationRunEntity $run, Context $context): array;

    public function calculateCurrentTotals(string $runId, bool $isWritten, Context $context): array;

    public function updateConnectionCredentials(Context $context, string $connectionUuid, array $credentialFields): void;

    public function finishMigration(string $runUuid, Context $context): void;

    public function assignThemeToSalesChannel(string $runUuid, Context $context): void;

    public function cleanupMigrationData(): void;
}
