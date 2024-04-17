<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Data;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

#[Package('services-settings')]
class SwagMigrationDataEntity extends Entity
{
    use EntityIdTrait;

    protected string $runId = '';

    protected ?string $entity = null;

    protected int $autoIncrement = 0;

    /**
     * @var array<mixed>|null
     */
    protected ?array $raw = null;

    /**
     * @var array<mixed>|null
     */
    protected ?array $converted = null;

    /**
     * @var array<mixed>|null
     */
    protected ?array $unmapped = null;

    protected ?string $mappingUuid = null;

    protected bool $written = false;

    protected bool $convertFailure = false;

    protected bool $writeFailure = false;

    protected SwagMigrationRunEntity $run;

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getAutoIncrement(): int
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(int $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return array<mixed>|null
     */
    public function getRaw(): ?array
    {
        return $this->raw;
    }

    /**
     * @param array<mixed> $raw
     */
    public function setRaw(array $raw): void
    {
        $this->raw = $raw;
    }

    /**
     * @return array<mixed>|null
     */
    public function getConverted(): ?array
    {
        return $this->converted;
    }

    /**
     * @param array<mixed> $converted
     */
    public function setConverted(array $converted): void
    {
        $this->converted = $converted;
    }

    /**
     * @return array<mixed>|null
     */
    public function getUnmapped(): ?array
    {
        return $this->unmapped;
    }

    /**
     * @param array<mixed> $unmapped
     */
    public function setUnmapped(array $unmapped): void
    {
        $this->unmapped = $unmapped;
    }

    public function getMappingUuid(): ?string
    {
        return $this->mappingUuid;
    }

    public function setMappingUuid(?string $mappingUuid): void
    {
        $this->mappingUuid = $mappingUuid;
    }

    public function getWritten(): bool
    {
        return $this->written;
    }

    public function setWritten(bool $written): void
    {
        $this->written = $written;
    }

    public function getConvertFailure(): bool
    {
        return $this->convertFailure;
    }

    public function setConvertFailure(bool $convertFailure): void
    {
        $this->convertFailure = $convertFailure;
    }

    public function getWriteFailure(): bool
    {
        return $this->writeFailure;
    }

    public function setWriteFailure(bool $writeFailure): void
    {
        $this->writeFailure = $writeFailure;
    }

    public function getRun(): SwagMigrationRunEntity
    {
        return $this->run;
    }

    public function setRun(SwagMigrationRunEntity $run): void
    {
        $this->run = $run;
    }
}
