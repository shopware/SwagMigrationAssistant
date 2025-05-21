<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Helper;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
class ConnectionNameSanitizer
{
    /**
     * Sanitizes the connection name by removing any special characters, hyphens and spaces.
     * Only alphanumeric characters are allowed.
     * We sometimes use the connection name in places where a unique technical name is needed and e.g. CustomFields
     */
    public static function sanitize(string $connectionName): string
    {
        return (string) \preg_replace('/[^a-zA-Z0-9]/', '', \str_replace([' ', '-'], '', $connectionName));
    }
}
