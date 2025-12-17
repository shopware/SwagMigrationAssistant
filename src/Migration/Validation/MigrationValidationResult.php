<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;

/**
 * @final
 */
#[Package('fundamentals@after-sales')]
class MigrationValidationResult
{
    /**
     * @param MigrationLogEntry[] $logs
     */
    public function __construct(
        private readonly string $entityName,
        private array $logs = [],
    ) {
    }

    public function addLog(MigrationLogEntry $log): void
    {
        $this->logs[] = $log;
    }

    /**
     * @return MigrationLogEntry[]
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function hasLogs(): bool
    {
        return \count($this->logs) !== 0;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }
}
