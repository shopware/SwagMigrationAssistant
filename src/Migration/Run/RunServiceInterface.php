<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface RunServiceInterface
{
    public function getRunStatus(Context $context): MigrationProgress;

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

    public function cleanupMigrationData(): void;
}
