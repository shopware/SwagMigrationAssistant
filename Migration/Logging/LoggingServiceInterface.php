<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\Context;

interface LoggingServiceInterface
{
    public function addError(Context $context, string $runId, string $title, array $details = NULL): void;
}