<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Service\ProgressState;

interface RunServiceInterface
{
    public function takeoverMigration(string $runUuid, Context $context): string;

    public function createMigrationRun(
        string $profileId,
        array $totals,
        array $additionalData,
        Context $context
    ): ?ProgressState;
}
