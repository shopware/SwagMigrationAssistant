<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Logging;

use SwagMigrationNext\Migration\Logging\LoggingService;

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
}
