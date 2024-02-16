<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\ProgressState;

#[Package('services-settings')]
interface RunServiceInterface
{
    public function takeoverMigration(string $runUuid, Context $context): string;

    public function abortMigration(string $runUuid, Context $context): void;

    public function cleanupMappingChecksums(string $connectionUuid, Context $context): void;

    /**
     * @param array<int, string> $dataSelectionIds
     */
    public function createMigrationRun(
        MigrationContextInterface $migrationContext,
        array $dataSelectionIds,
        Context $context
    ): ?ProgressState;

    /**
     * @return array<int, array{ id: string, entities: array<int, array{ entityName: string, currentCount: int, total: int }>, currentCount: int, total: int }>
     */
    public function calculateWriteProgress(SwagMigrationRunEntity $run, Context $context): array;

    /**
     * @return array<int, array{ id: string, entities: array<int, array{ entityName: string, currentCount: int, total: int }>, currentCount: int, total: int }>
     */
    public function calculateMediaFilesProgress(SwagMigrationRunEntity $run, Context $context): array;

    /**
     * @return array<int>
     */
    public function calculateCurrentTotals(string $runId, bool $isWritten, Context $context): array;

    /**
     * @param array<int, string>|null $credentialFields
     */
    public function updateConnectionCredentials(Context $context, string $connectionUuid, ?array $credentialFields): void;

    public function finishMigration(string $runUuid, Context $context): void;

    public function assignThemeToSalesChannel(string $runUuid, Context $context): void;

    public function cleanupMigrationData(): void;
}
