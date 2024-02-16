<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
final class RunOptions
{
    public function __construct(
        private readonly bool $keepData = false,
        private readonly bool $resumeExistingRun = false
    ) {
    }

    public function shouldKeepData(): bool
    {
        return $this->keepData;
    }

    public function shouldResumeExistingRun(): bool
    {
        return $this->resumeExistingRun;
    }
}
