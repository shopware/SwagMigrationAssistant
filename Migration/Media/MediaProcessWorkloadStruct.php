<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Struct\Struct;

class MediaProcessWorkloadStruct extends Struct
{
    final public const IN_PROGRESS_STATE = 'inProgress';
    final public const FINISH_STATE = 'finished';
    final public const ERROR_STATE = 'error';

    public function __construct(
        private readonly string $mediaId,
        private readonly string $runId,
        private readonly string $state = self::IN_PROGRESS_STATE,
        private readonly array $additionalData = [],
        private readonly int $errorCount = 0,
        private readonly int $currentOffset = 0
    ) {
    }

    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function setRunId(string $runId): void
    {
        $this->runId = $runId;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(array $additionalData): void
    {
        $this->additionalData = $additionalData;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function setErrorCount(int $errorCount): void
    {
        $this->errorCount = $errorCount;
    }

    public function getCurrentOffset(): int
    {
        return $this->currentOffset;
    }

    public function setCurrentOffset(int $currentOffset): void
    {
        $this->currentOffset = $currentOffset;
    }
}
