<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

/**
 * @example
 * $log = (new SwagMigrationLogBuilder('runId', 'profileName', 'gatewayName'))
 *     ->withField('fieldName')
 *     ->withFieldSourcePath('sourcePath')
 *     ->build(SwagMigrationLogEntry::class);
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
        protected ?string $entityName = null,
        protected ?string $fieldName = null,
        protected ?string $fieldSourcePath = null,
        protected ?array $sourceData = null,
        protected ?array $convertedData = null,
        protected ?array $usedMapping = null,
        protected ?string $exceptionMessage = null,
        protected ?array $exceptionTrace = null,
    ) {
    }

    public static function fromMigrationContext(MigrationContextInterface $migrationContext): self
    {
        return new self(
            $migrationContext->getRunUuid(),
            $migrationContext->getConnection()->getProfileName(),
            $migrationContext->getConnection()->getGatewayName(),
        );
    }

    public function withEntityName(string $entityName): self
    {
        $this->entityName = $entityName;

        return $this;
    }

    public function withFieldName(string $field): self
    {
        $this->fieldName = $field;

        return $this;
    }

    public function withFieldSourcePath(string $fieldSourcePath): self
    {
        $this->fieldSourcePath = $fieldSourcePath;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $sourceData
     */
    public function withSourceData(array $sourceData): self
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $convertedData
     */
    public function withConvertedData(array $convertedData): self
    {
        $this->convertedData = $convertedData;

        return $this;
    }

    /**
     * @param array<string, mixed> $usedMapping
     */
    public function withUsedMapping(array $usedMapping): self
    {
        $this->usedMapping = $usedMapping;

        return $this;
    }

    public function withExceptionMessage(string $exceptionMessage): self
    {
        $this->exceptionMessage = $exceptionMessage;

        return $this;
    }

    /**
     * @param array<int, array<string, mixed>> $exceptionTrace
     */
    public function withExceptionTrace(array $exceptionTrace): self
    {
        $this->exceptionTrace = $exceptionTrace;

        return $this;
    }

    /**
     * @template T of AbstractSwagMigrationLogEntry
     *
     * @param class-string<T> $logClass
     *
     * @return T
     */
    public function build(string $logClass): AbstractSwagMigrationLogEntry
    {
        if (!class_exists($logClass) || !is_subclass_of($logClass, AbstractSwagMigrationLogEntry::class)) {
            throw MigrationException::failedToCreateMigrationLog($logClass);
        }

        return new $logClass(
            $this->runId,
            $this->profileName,
            $this->gatewayName,
            $this->entityName,
            $this->fieldName,
            $this->fieldSourcePath,
            $this->sourceData,
            $this->convertedData,
            $this->usedMapping,
            $this->exceptionMessage,
            $this->exceptionTrace,
        );
    }
}
