<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('services-settings')]
final class MigrationProgress extends Struct
{
    protected int $progress;

    protected int $total;

    protected string $currentEntity;

    protected int $currentEntityProgress;

    protected ProgressDataSetCollection $dataSets;

    protected int $exceptionCount = 0;

    protected bool $isAborted = false;

    public function __construct(
        int $progress,
        int $total,
        ProgressDataSetCollection $dataSets,
        string $currentEntity,
        int $currentEntityProgress,
        int $exceptionCount = 0,
        bool $isAborted = false
    ) {
        $this->progress = $progress;
        $this->total = $total;
        $this->dataSets = $dataSets;
        $this->currentEntity = $currentEntity;
        $this->currentEntityProgress = $currentEntityProgress;
        $this->exceptionCount = $exceptionCount;
        $this->isAborted = $isAborted;
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

    public function getDataSets(): ProgressDataSetCollection
    {
        return $this->dataSets;
    }

    public function setDataSets(ProgressDataSetCollection $dataSets): void
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

    public function getCurrentEntityProgress(): int
    {
        return $this->currentEntityProgress;
    }

    public function setCurrentEntityProgress(int $currentEntityProgress): void
    {
        $this->currentEntityProgress = $currentEntityProgress;
    }

    public function getExceptionCount(): int
    {
        return $this->exceptionCount;
    }

    public function raiseExceptionCount(): void
    {
        ++$this->exceptionCount;
    }

    public function resetExceptionCount(): void
    {
        $this->exceptionCount = 0;
    }

    public function isAborted(): bool
    {
        return $this->isAborted;
    }

    public function setIsAborted(bool $isAborted): void
    {
        $this->isAborted = $isAborted;
    }
}
