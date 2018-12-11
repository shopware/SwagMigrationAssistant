<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use DateTime;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SwagMigrationLoggingStruct extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $logEntry;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLogEntry(): array
    {
        return $this->logEntry;
    }

    public function setLogEntry(array $logEntry): void
    {
        $this->logEntry = $logEntry;
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

    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
