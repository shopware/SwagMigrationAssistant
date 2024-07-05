<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\NoRunningMigrationException;

#[Package('services-settings')]
interface RunServiceInterface
{
    /**
     * Returns the progress of the running migration run.
     * If no migration run is running, it returns the progress with the step status IDLE.
     *
     * After starting the migration run, the steps are as follows, if the migration run is not aborted:
     * IDLE -> FETCHING -> WRITING -> MEDIA_PROCESSING -> CLEANUP -> INDEXING -> WAITING_FOR_APPROVE -> IDLE
     *
     * If the migration run is aborted, the steps are as follows:
     * IDLE -> [FETCHING || WRITING || MEDIA_PROCESSING] -> ABORTING -> CLEANUP -> INDEXING -> IDLE
     */
    public function getRunStatus(Context $context): MigrationState;

    /**
     * Abort the running migration.
     * If no migration run is running or the current migration is not in the FETCHING or WRITING or MEDIA_PROCESSING step, it throws a NoRunningMigrationException.
     *
     * @throws NoRunningMigrationException
     */
    public function abortMigration(Context $context): void;

    public function cleanupMappingChecksums(string $connectionUuid, Context $context): void;

    /**
     * @param array<int, string> $dataSelectionIds
     */
    public function startMigrationRun(array $dataSelectionIds, Context $context): void;

    /**
     * @param array<int, string>|null $credentialFields
     */
    public function updateConnectionCredentials(Context $context, string $connectionUuid, ?array $credentialFields): void;

    public function approveFinishingMigration(Context $context): void;

    public function assignThemeToSalesChannel(string $runUuid, Context $context): void;

    public function cleanupMigrationData(Context $context): void;
}
