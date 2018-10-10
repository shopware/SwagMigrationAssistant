<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Logging;

use SwagMigrationNext\Migration\Logging\LoggingService;

class DummyLoggingService extends LoggingService
{
    public function __construct()
    {
    }

    public function addInfo(string $runId, string $title, string $description, array $details = null): void
    {
    }

    public function addWarning(string $runId, string $title, string $description, array $details = null): void
    {
    }

    public function addError(string $runId, string $code, string $title, array $details = null): void
    {
    }

    public function saveLogging(\Shopware\Core\Framework\Context $context): void
    {
    }
}
