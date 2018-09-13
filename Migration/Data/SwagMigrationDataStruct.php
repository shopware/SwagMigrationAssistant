<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Data;

use DateTime;
use Shopware\Core\Framework\ORM\Entity;
use SwagMigrationNext\Migration\Run\SwagMigrationRunStruct;

class SwagMigrationDataStruct extends Entity
{
    /**
     * @var string
     */
    protected $runId;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var array
     */
    protected $raw;

    /**
     * @var array
     */
    protected $converted;

    /**
     * @var array
     */
    protected $unmapped;

    /**
     * @var bool
     */
    protected $written;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    /**
     * @var SwagMigrationRunStruct
     */
    protected $run;

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getRaw(): ?array
    {
        return $this->raw;
    }

    public function setRaw(?array $raw): void
    {
        $this->raw = $raw;
    }

    public function getConverted(): ?array
    {
        return $this->converted;
    }

    public function setConverted(?array $converted): void
    {
        $this->converted = $converted;
    }

    public function getUnmapped(): ?array
    {
        return $this->unmapped;
    }

    public function setUnmapped(?array $unmapped): void
    {
        $this->unmapped = $unmapped;
    }

    public function getWritten(): bool
    {
        return $this->written;
    }

    public function setWritten(bool $written): void
    {
        $this->written = $written;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getRun(): SwagMigrationRunStruct
    {
        return $this->run;
    }

    public function setRun(SwagMigrationRunStruct $run): void
    {
        $this->run = $run;
    }
}
