<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware56;

use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class Shopware56Profile implements ShopwareProfileInterface
{
    public const PROFILE_NAME = 'shopware56';

    public const SOURCE_SYSTEM_NAME = 'Shopware';

    public const SOURCE_SYSTEM_VERSION = '5.6';

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
}
