<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware57;

use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class Shopware57Profile implements ShopwareProfileInterface
{
    public const PROFILE_NAME = 'shopware57';

    public const SOURCE_SYSTEM_NAME = 'Shopware';

    public const SOURCE_SYSTEM_VERSION = '5.7';

    public const AUTHOR_NAME = 'shopware AG';

    public const ICON_PATH = '/swagmigrationassistant/static/img/migration-assistant-plugin.svg';

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function getSourceSystemName(): string
    {
        return self::SOURCE_SYSTEM_NAME;
    }

    public function getVersion(): string
    {
        return self::SOURCE_SYSTEM_VERSION;
    }

    public function getAuthorName(): string
    {
        return self::AUTHOR_NAME;
    }

    public function getIconPath(): string
    {
        return self::ICON_PATH;
    }
}
