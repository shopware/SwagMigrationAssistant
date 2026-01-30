<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertObjectTypeUnsupportedLog;

#[Package('fundamentals@after-sales')]
readonly class ConvertTranslationTypeUnsupportedLog extends ConvertObjectTypeUnsupportedLog
{
    public static function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }
}
