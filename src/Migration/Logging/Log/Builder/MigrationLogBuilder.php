<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log\Builder;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

/**
 * @final
 *
 * @example
 * $log = (new MigrationLogBuilder('runId', 'profileName', 'gatewayName'))
 *     ->withField('fieldName')
 *     ->withFieldSourcePath('sourcePath')
 *     ->build(MigrationLogEntry::class);
 */
#[Package('fundamentals@after-sales')]
class MigrationLogBuilder
{
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

    public function withEntityId(?string $entityId): self
    {
        $this->entityId = $this->getRevisedId($entityId);

        return $this;
    }

    /**
     * @param array<mixed> $sourceData
     */
    public function withSourceData(array $sourceData): self
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    /**
     * @param array<mixed> $convertedData
     */
    public function withConvertedData(array $convertedData): self
    {
        $this->convertedData = $convertedData;

        return $this;
    }

    public function withExceptionMessage(string $exceptionMessage): self
    {
        $this->exceptionMessage = $exceptionMessage;

        return $this;
    }

    /**
     * @param array<mixed> $exceptionTrace
     */
    public function withExceptionTrace(array $exceptionTrace): self
    {
        $this->exceptionTrace = $exceptionTrace;

        return $this;
    }

    /**
     * @template T of AbstractMigrationLogEntry
     *
     * @param class-string<T> $logClass
     *
     * @return T
     */
    public function build(string $logClass): AbstractMigrationLogEntry
    {
        \assert(\class_exists($logClass) && \is_subclass_of($logClass, AbstractMigrationLogEntry::class));

        return new $logClass(
            $this->runId,
            $this->profileName,
            $this->gatewayName,
            $this->entityId,
            $this->entityName,
            $this->fieldName,
            $this->fieldSourcePath,
            $this->sourceData,
            $this->convertedData,
            $this->exceptionMessage,
            $this->exceptionTrace,
        );
    }

    private function getRevisedId(?string $id): ?string
    {
        if ($id === null) {
            return null;
        }

        if (Uuid::isValid($id)) {
            return $id;
        }

        return null;
    }
}
