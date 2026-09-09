<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal Probe for shopware/shopware#18667 — DO NOT MERGE.
 *
 * Simulates code under test terminating the PHPUnit process with a success
 * exit code (the shopware/shopware#18560 incident class). The suite runs in
 * random order, so this kills the process after an arbitrary prefix of the
 * 234 test classes has run.
 *
 * Expected outcomes for the "PHPUnit" job of the Integration workflow:
 * - green  => the job cannot tell a full run from a truncated one, the issue
 *             is present and needs a fix in this repository.
 * - red    => platform's CompletionGuard (registered by TestBootstrapper::bootstrap(),
 *             which tests/TestBootstrap.php calls) already covers this repository.
 */
#[Package('fundamentals@after-sales')]
class CompletionGuardDemoKillerTest extends TestCase
{
    public function testExitZeroKillsThePhpunitProcess(): void
    {
        exit(0);
    }
}
