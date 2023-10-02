<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class MediaProcessWorkloadStruct extends Struct
{
    final public const IN_PROGRESS_STATE = 'inProgress';
    final public const FINISH_STATE = 'finished';
    final public const ERROR_STATE = 'error';

    private string $mediaId;

    private string $runId;

    private string $state;

    private array $additionalData;

    private int $errorCount;

    private int $currentOffset;

    public function __construct(
        string $mediaId,
        string $runId,
        string $state = self::IN_PROGRESS_STATE,
        array $additionalData = [],
        int $errorCount = 0,
        int $currentOffset = 0
    ) {
        $this->mediaId = $mediaId;
        $this->runId = $runId;
        $this->state = $state;
        $this->additionalData = $additionalData;
        $this->errorCount = $errorCount;
        $this->currentOffset = $currentOffset;
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
