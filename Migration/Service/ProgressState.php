<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Struct\Struct;

class ProgressState extends Struct
{
    /**
     * string
     */
    protected const STATUS_FETCH_DATA = 'fetchData';
    protected const STATUS_WRITE_DATA = 'writeData';
    protected const STATUS_DOWNLOAD_DATA = 'downloadData';

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var array
     */
    protected $profile;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var bool
     */
    protected $migrationRunning;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var array
     */
    protected $entityGroups;

    /**
     * @var int
     */
    protected $entityCount;

    /**
     * @var int
     */
    protected $finishedCount;

    /**
     * ProgressStateStruct constructor.
     *
     * @param string $status
     * @param bool   $isMigrationRunning
     * @param string $entity
     * @param int    $finishedCount
     */
    public function __construct(bool $isMigrationRunning, string $status = null, string $entity = null, int $finishedCount = null, int $entityCount = null)
    {
        $this->status = $status;
        $this->migrationRunning = $isMigrationRunning;
        $this->entity = $entity;
        $this->finishedCount = $finishedCount;
        $this->entityCount = $entityCount;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getProfile(): array
    {
        return $this->profile;
    }

    public function setProfile(array $profile): void
    {
        $this->profile = $profile;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function migrationRunning(): bool
    {
        return $this->migrationRunning;
    }

    public function setMigrationRunning(bool $migrationRunning): void
    {
        $this->migrationRunning = $migrationRunning;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    public function getEntityGroups(): array
    {
        return $this->entityGroups;
    }

    public function setEntityGroups(array $entityGroups): void
    {
        $this->entityGroups = $entityGroups;
    }

    public function getEntityCount(): int
    {
        return $this->entityCount;
    }

    public function setEntityCount(int $entityCount): void
    {
        $this->entityCount = $entityCount;
    }

    public function getFinishedCount(): int
    {
        return $this->finishedCount;
    }

    public function setFinishedCount(int $finishedCount): void
    {
        $this->finishedCount = $finishedCount;
    }
}
