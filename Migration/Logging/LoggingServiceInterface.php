<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\Context;

interface LoggingServiceInterface
{
    public function addInfo(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void;

    public function addWarning(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void;

    public function addError(string $runId, string $code, string $title, string $description, array $details = [], int $counting = 0): void;

    public function saveLogging(Context $context): void;
}
