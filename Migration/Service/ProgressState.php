<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Struct\Struct;
use SwagMigrationNext\Migration\Run\RunProgress;

class ProgressState extends Struct
{
    public const STATUS_WAITING = -1;
    public const STATUS_FETCH_DATA = 0;
    public const STATUS_WRITE_DATA = 1;
    public const STATUS_DOWNLOAD_DATA = 2;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var bool
     */
    protected $migrationRunning;

    /**
     * @var bool
     */
    protected $validMigrationRunToken;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var int
     */
    protected $entityCount;

    /**
     * @var int
     */
    protected $finishedCount;

    /**
     * @var array
     */
    protected $runProgress;

    /**
     * @param array $runProgress
     */
    public function __construct(
        bool $isMigrationRunning,
        bool $validMigrationRunToken,
        string $runId = null,
        string $accessToken = null,
        int $status = ProgressState::STATUS_WAITING,
        string $entity = null,
        int $finishedCount = 0,
        int $entityCount = 0,
        array $runProgress = []
    ) {
        $this->migrationRunning = $isMigrationRunning;
        $this->runId = $runId;
        $this->validMigrationRunToken = $validMigrationRunToken;
        $this->accessToken = $accessToken;
        $this->status = $status;
        $this->entity = $entity;
        $this->finishedCount = $finishedCount;
        $this->entityCount = $entityCount;
        $this->runProgress = $runProgress;
    }

    public function isMigrationRunning(): bool
    {
        return $this->migrationRunning;
    }

    public function isMigrationRunTokenValid(): bool
    {
        return $this->validMigrationRunToken;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getFinishedCount(): int
    {
        return $this->finishedCount;
    }

    public function getEntityCount(): int
    {
        return $this->entityCount;
    }

    public function isValidMigrationRunToken(): bool
    {
        return $this->validMigrationRunToken;
    }

    public function getRunProgress(): array
    {
        return $this->runProgress;
    }

    public function setRunProgress(array $runProgress): void
    {
        $this->runProgress = $runProgress;
    }
}
