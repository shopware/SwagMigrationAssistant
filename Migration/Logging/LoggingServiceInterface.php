<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\Context;

interface LoggingServiceInterface
{
    public function addInfo(string $runId, string $title, string $description, array $details = null): void;

    public function addWarning(string $runId, string $title, string $description, array $details = null): void;

    public function addError(string $runId, string $code, string $title, array $details = null): void;

    public function saveLogging(Context $context): void;
}
