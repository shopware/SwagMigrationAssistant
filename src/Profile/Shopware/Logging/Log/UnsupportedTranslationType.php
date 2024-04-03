<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\UnsupportedObjectType;

#[Package('services-settings')]
class UnsupportedTranslationType extends UnsupportedObjectType
{
    public function getLevel(): string
    {
        return self::LOG_LEVEL_INFO;
    }
}
