<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use DateTime;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagMigrationNext\Exception\MigrationRunUndefinedStatusException;
use SwagMigrationNext\Migration\Asset\SwagMigrationMediaFileStruct;
use SwagMigrationNext\Migration\Data\SwagMigrationDataStruct;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileStruct;

class SwagMigrationRunStruct extends Entity
{
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
     * @var string
     */
    protected $profileId;

    /**
     * @var SwagMigrationProfileStruct
     */
    protected $profile;

    /**
     * @var array|null
     */
    protected $totals;

    /**
     * @var array|null
     */
    protected $additionalData;

    /**
     * @var string
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
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     */
    protected $updatedAt;

    /**
     * @var SwagMigrationDataStruct[]
     */
    protected $data;

    /**
     * @var SwagMigrationMediaFileStruct[]
     */
    protected $mediaFiles;

    public function getProfileId(): string
    {
        return $this->profileId;
    }

    public function setProfileId(string $profileId): void
    {
        $this->profileId = $profileId;
    }

    public function getProfile(): SwagMigrationProfileStruct
    {
        return $this->profile;
    }

    public function setProfile(SwagMigrationProfileStruct $profile): void
    {
        $this->profile = $profile;
    }

    public function getTotals(): ?array
    {
        return $this->totals;
    }

    public function setTotals(array $totals): void
    {
        $this->totals = $totals;
    }

    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(array $additionalData): void
    {
        $this->additionalData = $additionalData;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @throws MigrationRunUndefinedStatusException
     */
    public function setStatus(string $status): void
    {
        if ($status !== self::STATUS_RUNNING &&
            $status !== self::STATUS_FINISHED &&
            $status !== self::STATUS_ABORTED
        ) {
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

    /**
     * @return SwagMigrationDataStruct[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param SwagMigrationDataStruct[] $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return SwagMigrationMediaFileStruct[]
     */
    public function getMediaFiles(): array
    {
        return $this->mediaFiles;
    }

    /**
     * @param SwagMigrationMediaFileStruct[] $mediaFiles
     */
    public function setMediaFiles(array $mediaFiles): void
    {
        $this->mediaFiles = $mediaFiles;
    }
}
