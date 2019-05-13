<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Logging;

use SwagMigrationAssistant\Migration\Logging\LoggingService;

class DummyLoggingService extends LoggingService
{
    public function __construct()
    {
    }

    public function getLoggingArray(): array
    {
        return $this->logging;
    }

    public function saveLogging(\Shopware\Core\Framework\Context $context): void
    {
    }

    public function resetLogging(): void
    {
        $this->logging = [];
    }
}
