<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;

/**
 * @example
 * $log = (new SwagMigrationLogBuilder('runId', 'profileName', 'gatewayName'))
 *     ->withField('fieldName')
 *     ->withFieldSourcePath('sourcePath')
 *     ->buildLogEntry(SwagMigrationLogRecord::class);
 */
#[Package('fundamentals@after-sales')]
class SwagMigrationLogBuilder
{
    /**
     * @param array<int, array<string, mixed>>|null $sourceData
     * @param array<int, array<string, mixed>>|null $convertedData
     * @param array<string, mixed>|null $usedMapping
     * @param array<int, array<string, mixed>>|null $exceptionTrace
     */
    public function __construct(
        protected string $runId,
        protected string $profileName,
        protected string $gatewayName,
        protected ?string $field = null,
        protected ?string $fieldSourcePath = null,
        protected ?array $sourceData = null,
        protected ?array $convertedData = null,
        protected ?array $usedMapping = null,
        protected ?string $exceptionMessage = null,
        protected ?array $exceptionTrace = null,
    ) {
    }

    public function withField(?string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function withFieldSourcePath(?string $fieldSourcePath): self
    {
        $this->fieldSourcePath = $fieldSourcePath;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>>|null $sourceData
     */
    public function withSourceData(?array $sourceData): self
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>>|null $convertedData
     */
    public function withConvertedData(?array $convertedData): self
    {
        $this->convertedData = $convertedData;

        return $this;
    }

    /**
     * @param array<string, mixed>|null $usedMapping
     */
    public function withUsedMapping(?array $usedMapping): self
    {
        $this->usedMapping = $usedMapping;

        return $this;
    }

    public function withExceptionMessage(?string $exceptionMessage): self
    {
        $this->exceptionMessage = $exceptionMessage;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>>|null $exceptionTrace
     */
    public function withExceptionTrace(?array $exceptionTrace): self
    {
        $this->exceptionTrace = $exceptionTrace;

        return $this;
    }

    public function buildRecord(): SwagMigrationLogRecord
    {
        return new SwagMigrationLogRecord(
            $this->runId,
            $this->profileName,
            $this->gatewayName,
            $this->field,
            $this->fieldSourcePath,
            $this->sourceData,
            $this->convertedData,
            $this->usedMapping,
            $this->exceptionMessage,
            $this->exceptionTrace,
        );
    }

    /**
     * @template T of AbstractSwagMigrationLogEntry
     *
     * @param class-string<T> $logClass The class name of the log entry to create
     *
     * @return T The created log entry instance
     */
    public function buildLogEntry(string $logClass): AbstractSwagMigrationLogEntry
    {
        $record = $this->buildRecord();

        return new $logClass($record);
    }
}
