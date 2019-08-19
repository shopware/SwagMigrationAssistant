<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use SwagMigrationAssistant\Migration\Logging\Log\UnsupportedObjectType;

class UnsupportedTranslationType extends UnsupportedObjectType
{
    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }
}
