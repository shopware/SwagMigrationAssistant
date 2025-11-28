<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('fundamentals@after-sales')]
class SwagMigrationLoggingEntity extends Entity
{
    use EntityIdTrait;

    protected string $runId;

    protected ?SwagMigrationRunEntity $run = null;

    protected string $profileName;

    protected string $gatewayName;

    protected string $level;

    protected string $code;

    protected bool $userFixable;

    protected int $autoIncrement;

    protected ?string $entityName = null;

    protected ?string $fieldName = null;

    protected ?string $fieldSourcePath = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $sourceData = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $convertedData = null;

    protected ?string $exceptionMessage = null;

    protected ?string $entityId;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $exceptionTrace = null;

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getRun(): ?SwagMigrationRunEntity
    {
        return $this->run;
    }

    public function setRun(SwagMigrationRunEntity $run): void
    {
        $this->run = $run;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function setProfileName(string $profileName): void
    {
        $this->profileName = $profileName;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function setGatewayName(string $gatewayName): void
    {
        $this->gatewayName = $gatewayName;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): void
    {
        $this->level = $level;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function isUserFixable(): bool
    {
        return $this->userFixable;
    }

    public function setUserFixable(bool $userFixable): void
    {
        $this->userFixable = $userFixable;
    }

    public function getAutoIncrement(): int
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(int $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    public function getFieldSourcePath(): ?string
    {
        return $this->fieldSourcePath;
    }

    public function setFieldSourcePath(string $fieldSourcePath): void
    {
        $this->fieldSourcePath = $fieldSourcePath;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getSourceData(): ?array
    {
        return $this->sourceData;
    }

    /**
     * @param array<int, array<string, mixed>> $sourceData
     */
    public function setSourceData(array $sourceData): void
    {
        $this->sourceData = $sourceData;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getConvertedData(): ?array
    {
        return $this->convertedData;
    }

    /**
     * @param array<int, array<string, mixed>> $convertedData
     */
    public function setConvertedData(array $convertedData): void
    {
        $this->convertedData = $convertedData;
    }

    public function getExceptionMessage(): ?string
    {
        return $this->exceptionMessage;
    }

    public function setExceptionMessage(string $exceptionMessage): void
    {
        $this->exceptionMessage = $exceptionMessage;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getExceptionTrace(): ?array
    {
        return $this->exceptionTrace;
    }

    /**
     * @param array<int, array<string, mixed>> $exceptionTrace
     */
    public function setExceptionTrace(array $exceptionTrace): void
    {
        $this->exceptionTrace = $exceptionTrace;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(?string $entityId): void
    {
        $this->entityId = $entityId;
    }
}
