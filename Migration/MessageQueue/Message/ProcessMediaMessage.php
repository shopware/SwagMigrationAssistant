<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

#[Package('services-settings')]
class ProcessMediaMessage implements AsyncMessageInterface
{
    /**
     * @var string[]
     */
    private array $mediaFileIds;

    private string $runId;

    private string $contextData;

    private string $entityName;

    private int $fileChunkByteSize;

    public function withContext(Context $context): ProcessMediaMessage
    {
        $this->contextData = \serialize($context);

        return $this;
    }

    public function readContext(): Context
    {
        return \unserialize($this->contextData);
    }

    public function setContextData(string $contextData): void
    {
        $this->contextData = $contextData;
    }

    /**
     * @param string[] $mediaFileIds
     */
    public function setMediaFileIds(array $mediaFileIds): void
    {
        $this->mediaFileIds = $mediaFileIds;
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

    /**
     * @return string[]
     */
    public function getMediaFileIds(): array
    {
        return $this->mediaFileIds;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getFileChunkByteSize(): int
    {
        return $this->fileChunkByteSize;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }
}
