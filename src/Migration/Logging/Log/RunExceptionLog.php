<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;

#[Package('fundamentals@after-sales')]
readonly class RunExceptionLog extends AbstractMigrationLogEntry
{
    public static function isUserFixable(): bool
    {
        return false;
    }

    public static function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public static function getCode(): string
    {
        return 'SWAG_MIGRATION_RUN_EXCEPTION';
    }
}
