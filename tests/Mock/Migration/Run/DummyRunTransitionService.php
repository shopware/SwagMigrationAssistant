<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\Migration\Run;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Run\MigrationStep;
use SwagMigrationAssistant\Migration\Run\RunTransitionServiceInterface;

#[Package('services-settings')]
class DummyRunTransitionService implements RunTransitionServiceInterface
{
    public function __construct(
        private MigrationStep $activeStep
    ) {
    }

    public function transitionToRunStep(string $runId, MigrationStep $step): void
    {
        if ($this->activeStep === self::PROTECTED_STEP) {
            return;
        }

        $this->activeStep = $step;
    }

    public function forceTransitionToRunStep(string $runId, MigrationStep $step): void
    {
        $this->activeStep = $step;
    }

    public function getActiveStep(): MigrationStep
    {
        return $this->activeStep;
    }
}
