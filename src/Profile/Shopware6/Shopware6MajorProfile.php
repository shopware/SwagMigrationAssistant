<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
class Shopware6MajorProfile implements Shopware6ProfileInterface
{
    final public const PROFILE_NAME = 'shopware6major';

    final public const SOURCE_SYSTEM_NAME = 'Shopware';

    final public const AUTHOR_NAME = 'shopware AG';

    final public const ICON_PATH = '/swagmigrationassistant/static/img/migration-assistant-plugin.svg';

    private string $supportedVersion;

    public function __construct(string $shopwareVersion)
    {
        $selfVersionParts = \explode('.', $shopwareVersion);
        $this->supportedVersion = \implode('.', [
            $selfVersionParts[0], // 6
            $selfVersionParts[1], // major
        ]);
    }

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
        return $this->supportedVersion;
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
