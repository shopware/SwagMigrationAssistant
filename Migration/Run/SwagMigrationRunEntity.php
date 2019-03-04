<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationNext\Exception\MigrationRunUndefinedStatusException;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationNext\Migration\Media\SwagMigrationMediaFileCollection;

class SwagMigrationRunEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    public const STATUS_RUNNING = 'running';

    /**
     * @var string
     */
    public const STATUS_FINISHED = 'finished';

    /**
     * @var string
     */
    public const STATUS_ABORTED = 'aborted';

    /**
     * @var string|null
     */
    protected $connectionId;

    /**
     * @var SwagMigrationConnectionEntity|null
     */
    protected $connection;

    /**
     * @var array|null
     */
    protected $totals;

    /**
     * @var array|null
     */
    protected $environmentInformation;

    /**
     * @var string|null
     */
    protected $status;

    /**
     * @var string|null
     */
    protected $userId;

    /**
     * @var string|null
     */
    protected $accessToken;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     */
    protected $updatedAt;

    /**
     * @var RunProgress[]
     */
    protected $progress;

    /**
     * @var SwagMigrationMediaFileCollection|null
     */
    protected $mediaFiles;

    /**
     * @var SwagMigrationLoggingCollection|null
     */
    protected $logs;

    public function getConnectionId(): ?string
    {
        return $this->connectionId;
    }

    public function setConnectionId(string $connectionId): void
    {
        $this->connectionId = $connectionId;
    }

    public function getConnection(): ?SwagMigrationConnectionEntity
    {
        return $this->connection;
    }

    public function setConnection(SwagMigrationConnectionEntity $connection): void
    {
        $this->connection = $connection;
    }

    public function getTotals(): ?array
    {
        return $this->totals;
    }

    public function setTotals(array $totals): void
    {
        $this->totals = $totals;
    }

    public function getEnvironmentInformation(): ?array
    {
        return $this->environmentInformation;
    }

    public function setEnvironmentInformation(array $environmentInformation): void
    {
        $this->environmentInformation = $environmentInformation;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @throws MigrationRunUndefinedStatusException
     */
    public function setStatus(string $status): void
    {
        if (!in_array($status, [self::STATUS_RUNNING, self::STATUS_FINISHED, self::STATUS_ABORTED], true)) {
            throw new MigrationRunUndefinedStatusException($status);
        }

        $this->status = $status;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getProgress(): ?array
    {
        return $this->progress;
    }

    /**
     * @param RunProgress[] $progress
     */
    public function setProgress(array $progress): void
    {
        $this->progress = $progress;
    }

    public function getMediaFiles(): ?SwagMigrationMediaFileCollection
    {
        return $this->mediaFiles;
    }

    public function setMediaFiles(SwagMigrationMediaFileCollection $mediaFiles): void
    {
        $this->mediaFiles = $mediaFiles;
    }

    public function getLogs(): ?SwagMigrationLoggingCollection
    {
        return $this->logs;
    }

    public function setLogs(SwagMigrationLoggingCollection $logs): void
    {
        $this->logs = $logs;
    }
}
