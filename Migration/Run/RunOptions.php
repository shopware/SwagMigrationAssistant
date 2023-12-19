<?php

declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Run;

class RunOptions
{
    public function __construct(
        readonly bool $keepData = false,
        readonly bool $resumeExistingRun = false
    ) {}

    public function shouldKeepData(): bool
    {
        return $this->keepData;
    }

    public function shouldResumeExistingRun(): bool
    {
        return $this->resumeExistingRun;
    }
}
