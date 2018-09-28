<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use DateTime;
use Shopware\Core\Framework\ORM\Entity;
use SwagMigrationNext\Migration\Run\SwagMigrationRunStruct;

class SwagMigrationMediaFileStruct extends Entity
{
    /**
     * @var string
     */
    protected $runId;

    /**
     * @var SwagMigrationRunStruct
     */
    protected $run;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var int
     */
    protected $fileSize;

    /**
     * @var string
     */
    protected $mediaId;

    /**
     * @var bool
     */
    protected $written;

    /**
     * @var bool
     */
    protected $downloaded;

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

    public function getRun(): SwagMigrationRunStruct
    {
        return $this->run;
    }

    public function setRun(SwagMigrationRunStruct $run): void
    {
        $this->run = $run;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getWritten(): bool
    {
        return $this->written;
    }

    public function setWritten(bool $written): void
    {
        $this->written = $written;
    }

    public function getDownloaded(): bool
    {
        return $this->downloaded;
    }

    public function setDownloaded(bool $downloaded): void
    {
        $this->downloaded = $downloaded;
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
