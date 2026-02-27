<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;

#[Package('fundamentals@after-sales')]
readonly class DebugLog implements MigrationLogEntry
{
    /**
     * @param array<string, mixed> $logData
     */
    public function __construct(
        private string $runId,
        private array $logData,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getLogData(): array
    {
        return $this->logData;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public static function isUserFixable(): bool
    {
        return false;
    }

    public function getProfileName(): string
    {
        return 'Debug Profile';
    }

    public function getGatewayName(): string
    {
        return 'Debug Gateway';
    }

    public static function getLevel(): string
    {
        return AbstractMigrationLogEntry::LOG_LEVEL_DEBUG;
    }

    public static function getCode(): string
    {
        return 'SWAG_MIGRATION_DEBUG';
    }

    public function getEntityName(): ?string
    {
        return null;
    }

    public function getFieldName(): ?string
    {
        return null;
    }

    public function getFieldSourcePath(): ?string
    {
        return null;
    }

    public function getSourceData(): ?array
    {
        return null;
    }

    public function getConvertedData(): ?array
    {
        return null;
    }

    public function getExceptionMessage(): ?string
    {
        return null;
    }

    public function getExceptionTrace(): ?array
    {
        return null;
    }

    public function getEntityId(): ?string
    {
        return null;
    }
}
