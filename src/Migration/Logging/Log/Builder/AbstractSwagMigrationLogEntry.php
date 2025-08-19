<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
abstract readonly class AbstractSwagMigrationLogEntry implements SwagMigrationLogEntry
{
    final public const LOG_LEVEL_INFO = 'info';
    final public const LOG_LEVEL_WARNING = 'warning';
    final public const LOG_LEVEL_ERROR = 'error';
    final public const LOG_LEVEL_DEBUG = 'debug';

    public function __construct(
        protected SwagMigrationLogRecord $record,
    ) {
    }

    public function getRunId(): string
    {
        return $this->record->runId;
    }

    public function getProfileName(): string
    {
        return $this->record->profileName;
    }

    public function getGatewayName(): string
    {
        return $this->record->gatewayName;
    }

    public function getEntityName(): ?string
    {
        return $this->record->entityName;
    }

    public function getFieldName(): ?string
    {
        return $this->record->fieldName;
    }

    public function getFieldSourcePath(): ?string
    {
        return $this->record->fieldSourcePath;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getSourceData(): ?array
    {
        return $this->record->sourceData;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getConvertedData(): ?array
    {
        return $this->record->convertedData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUsedMapping(): ?array
    {
        return $this->record->usedMapping;
    }

    public function getExceptionMessage(): ?string
    {
        return $this->record->exceptionMessage;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getExceptionTrace(): ?array
    {
        return $this->record->exceptionTrace;
    }
}
