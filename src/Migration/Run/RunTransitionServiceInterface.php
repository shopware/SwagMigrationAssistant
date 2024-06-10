<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
interface RunTransitionServiceInterface
{
    /**
     * @var MigrationStep
     */
    public const PROTECTED_STEP = MigrationStep::ABORTING;

    /**
     * Ensures to not transition away from self::PROTECTED_STEP or in other words
     * Don't override a run in a protected step.
     */
    public function transitionToRunStep(string $runId, MigrationStep $step): void;

    /**
     * Forcefully transitions a run to the given step.
     *
     * # Safety:
     * To prevent data races this should only be called by the MigrationProcessHandler to
     * transition away from a protected step.
     */
    public function forceTransitionToRunStep(string $runId, MigrationStep $step): void;
}
