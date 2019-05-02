<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Media;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwagMigrationNext\Migration\Run\SwagMigrationRunEntity;

class SwagMigrationMediaFileEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var SwagMigrationRunEntity
     */
    protected $run;

    /**
     * @var string
     */
    protected $fileName;

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
    protected $processed;

    /**
     * @var bool
     */
    protected $processFailure;

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getRun(): SwagMigrationRunEntity
    {
        return $this->run;
    }

    public function setRun(SwagMigrationRunEntity $run): void
    {
        $this->run = $run;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
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

    public function getProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    public function getProcessFailure(): bool
    {
        return $this->processFailure;
    }

    public function setProcessFailure(bool $processFailure): void
    {
        $this->processFailure = $processFailure;
    }
}
