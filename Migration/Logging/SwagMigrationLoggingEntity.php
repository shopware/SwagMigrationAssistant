<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

class SwagMigrationLoggingEntity extends Entity
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
     * @var SwagMigrationRunEntity
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

    public function getRun(): SwagMigrationRunEntity
    {
        return $this->run;
    }

    public function setRun(SwagMigrationRunEntity $run): void
    {
        $this->run = $run;
    }
}
