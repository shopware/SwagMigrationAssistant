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
final class MigrationState extends Struct
{
    protected MigrationStep $step;

    protected int $progress;

    protected int $total;

    public function __construct(MigrationStep $step, int $progress, int $total)
    {
        $this->step = $step;
        $this->progress = $progress;
        $this->total = $total;
    }

    public function getStepValue(): string
    {
        return $this->step->value;
    }

    public function getStep(): MigrationStep
    {
        return $this->step;
    }

    public function setStep(MigrationStep $step): void
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
}
