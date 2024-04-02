<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\History;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface HistoryServiceInterface
{
    public function getGroupedLogsOfRun(
        string $runUuid,
        Context $context
    ): array;

    /**
     * @return \Closure Used as the StreamedResponse callback.
     *                  Use print / echo inside the Closure to write strings into the log file.
     */
    public function downloadLogsOfRun(string $runUuid, Context $context): \Closure;

    public function clearDataOfRun(string $runUuid, Context $context): void;

    public function isMediaProcessing(): bool;
}
