<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Struct\Struct;

final class MigrationProgress extends Struct
{
    public const STATUS_IDLE = 'idle';

    public const STATUS_FETCHING = 'fetching';

    public const STATUS_WRITING = 'writing';

    public const STATUS_MEDIA_PROCESSING = 'media-processing';

    public const STATUS_CLEANUP = 'cleanup';

    public const STATUS_INDEXING = 'indexing';

    public const STATUS_WAITING_FOR_APPROVE = 'waiting-for-approve';

    public const STATUS_ABORTING = 'aborting';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_ABORTED = 'aborted';

    protected string $step;

    protected int $progress;

    protected int $total;

    protected string $currentEntity;

    protected int $currentProgress;

    /**
     * @var array<string>
     */
    protected array $dataSets;

    public function __construct(string $step, int $progress, int $total, array $dataSets, string $currentEntity, int $currentProgress)
    {
        $this->step = $step;
        $this->progress = $progress;
        $this->total = $total;
        $this->dataSets = $dataSets;
        $this->currentEntity = $currentEntity;
        $this->currentProgress = $currentProgress;
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function setStep(string $step): void
    {
        $this->step = $step;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): void
    {
        $this->progress = $progress;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getDataSets(): array
    {
        return $this->dataSets;
    }

    public function setDataSets(array $dataSets): void
    {
        $this->dataSets = $dataSets;
    }

    public function getCurrentEntity(): string
    {
        return $this->currentEntity;
    }

    public function setCurrentEntity(string $currentEntity): void
    {
        $this->currentEntity = $currentEntity;
    }

    public function getCurrentProgress(): int
    {
        return $this->currentProgress;
    }

    public function setCurrentProgress(int $currentProgress): void
    {
        $this->currentProgress = $currentProgress;
    }
}
