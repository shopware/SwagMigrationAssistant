<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationAssistant\Exception\MigrationRunUndefinedStatusException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Logging\SwagMigrationLoggingCollection;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\Premapping\PremappingStruct;

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
     * @var RunProgress[]
     */
    protected $progress;

    /**
     * @var PremappingStruct[]
     */
    protected $premapping;

    /**
     * @var SwagMigrationDataCollection|null
     */
    protected $data;

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
        if (!\in_array($status, [self::STATUS_RUNNING, self::STATUS_FINISHED, self::STATUS_ABORTED], true)) {
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

    public function getPremapping(): ?array
    {
        return $this->premapping;
    }

    /**
     * @param PremappingStruct[] $premapping
     */
    public function setPremapping(array $premapping): void
    {
        $this->premapping = $premapping;
    }

    public function getData(): ?SwagMigrationDataCollection
    {
        return $this->data;
    }

    public function setData(SwagMigrationDataCollection $data): void
    {
        $this->data = $data;
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
