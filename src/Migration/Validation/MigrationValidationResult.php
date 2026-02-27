<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;

/**
 * @final
 *
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
class MigrationValidationResult
{
    /**
     * @var array<string, MigrationLogEntry>
     */
    private array $logs = [];

    public function __construct(
        private readonly string $entityName,
    ) {
    }

    public function addLog(MigrationLogEntry $log): void
    {
        $key = $this->createLogKey($log);
        $this->logs[$key] = $log;
    }

    /**
     * @return MigrationLogEntry[]
     */
    public function getLogs(): array
    {
        return \array_values($this->logs);
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    private function createLogKey(MigrationLogEntry $log): string
    {
        return Hasher::hash(\implode('.', [
            $log->getCode(),
            $log->getEntityName() ?? '',
            $log->getEntityId() ?? '',
            $log->getFieldName() ?? '',
        ]));
    }
}
