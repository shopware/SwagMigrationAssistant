<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\History;

use Shopware\Core\Framework\Context;

interface HistoryServiceInterface
{
    public function getGroupedLogsOfRun(
        string $runUuid,
        int $offset,
        int $limit,
        string $sortBy,
        string $sortDirection,
        Context $context
    ): array;

    /**
     * @return \Closure Used as the StreamedResponse callback.
     *                  Use print / echo inside the Closure to write strings into the log file.
     */
    public function downloadLogsOfRun(string $runUuid, Context $context): \Closure;
}
