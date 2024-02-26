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
    protected MigrationProgressStatus $step;

    protected int $progress;

    protected int $total;

    protected string $currentEntity;

    protected int $currentEntityProgress;

    protected ProgressDataSetCollection $dataSets;

    public function __construct(MigrationProgressStatus $step, int $progress, int $total, ProgressDataSetCollection $dataSets, string $currentEntity, int $currentEntityProgress)
    {
        $this->step = $step;
        $this->progress = $progress;
        $this->total = $total;
        $this->dataSets = $dataSets;
        $this->currentEntity = $currentEntity;
        $this->currentEntityProgress = $currentEntityProgress;
    }

    public function getStepValue(): string
    {
        return $this->step->value;
    }

    public function getStep(): MigrationProgressStatus
    {
        return $this->step;
    }

    public function setStep(MigrationProgressStatus $step): void
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
}
