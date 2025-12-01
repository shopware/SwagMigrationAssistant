<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
abstract readonly class AbstractMigrationLogEntry implements MigrationLogEntry
{
    final public const LOG_LEVEL_INFO = 'info';
    final public const LOG_LEVEL_WARNING = 'warning';
    final public const LOG_LEVEL_ERROR = 'error';
    final public const LOG_LEVEL_DEBUG = 'debug';

    /**
     * @param array<mixed>|null $sourceData
     * @param array<mixed>|null $convertedData
     * @param array<mixed>|null $exceptionTrace
     */
    public function __construct(
        protected string $runId,
        protected string $profileName,
        protected string $gatewayName,
        protected ?string $entityId = null,
        protected ?string $entityName = null,
        protected ?string $fieldName = null,
        protected ?string $fieldSourcePath = null,
        protected ?array $sourceData = null,
        protected ?array $convertedData = null,
        protected ?string $exceptionMessage = null,
        protected ?array $exceptionTrace = null,
    ) {
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function getFieldSourcePath(): ?string
    {
        return $this->fieldSourcePath;
    }

    /**
     * @return array<mixed>|null
     */
    public function getSourceData(): ?array
    {
        return $this->sourceData;
    }

    /**
     * @return array<mixed>|null
     */
    public function getConvertedData(): ?array
    {
        return $this->convertedData;
    }

    public function getExceptionMessage(): ?string
    {
        return $this->exceptionMessage;
    }

    /**
     * @return array<mixed>|null
     */
    public function getExceptionTrace(): ?array
    {
        return $this->exceptionTrace;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }
}
