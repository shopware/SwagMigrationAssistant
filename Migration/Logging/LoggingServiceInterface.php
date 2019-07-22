<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;

interface LoggingServiceInterface
{
    public function addLogEntry(LogEntryInterface $logEntry): void;

    public function saveLogging(Context $context): void;
}
