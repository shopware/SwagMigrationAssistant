<?php declare(strict_types=1);


namespace SwagMigrationNext\Migration\Logging;


use DateTime;
use Shopware\Core\Framework\ORM\Entity;

class SwagMigrationLoggingStruct extends Entity
{
    /**
     * @var string
     */
    protected $runId;

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

    /**
     * @return string
     */
    public function getRunId(): string
    {
        return $this->runId;
    }

    /**
     * @param string $runId
     */
    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    /**
     * @return array
     */
    public function getLogEntry(): array
    {
        return $this->logEntry;
    }

    /**
     * @param array $logEntry
     */
    public function setLogEntry(array $logEntry): void
    {
        $this->logEntry = $logEntry;
    }
}