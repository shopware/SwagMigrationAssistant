<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\MessageQueue\Message;

use Shopware\Core\Framework\Context;

class ProcessMediaMessage
{
    /**
     * @var string
     */
    private $mediaFileId;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var string
     */
    private $contextData;

    /**
     * @var int
     */
    private $fileChunkByteSize;

    public function withContext(Context $context): ProcessMediaMessage
    {
        $this->contextData = serialize($context);

        return $this;
    }

    public function readContext(): Context
    {
        return unserialize($this->contextData);
    }

    public function setContextData(string $contextData): void
    {
        $this->contextData = $contextData;
    }

    public function setMediaFileId(string $mediaFileId): void
    {
        $this->mediaFileId = $mediaFileId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function setFileChunkByteSize(int $fileChunkByteSize): void
    {
        $this->fileChunkByteSize = $fileChunkByteSize;
    }

    public function getContextData(): string
    {
        return $this->contextData;
    }

    public function getMediaFileId(): string
    {
        return $this->mediaFileId;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getFileChunkByteSize(): int
    {
        return $this->fileChunkByteSize;
    }
}
