<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;

#[Package('fundamentals@after-sales')]
class Shopware5FingerprintProvider implements MigrationFingerprintProviderInterface
{
    public static function supports(string $profileName): bool
    {
        return \in_array(
            $profileName,
            [
                Shopware54Profile::PROFILE_NAME,
                Shopware55Profile::PROFILE_NAME,
                Shopware56Profile::PROFILE_NAME,
                Shopware57Profile::PROFILE_NAME,
            ],
            true
        );
    }

    /**
     * @param array<string, mixed>|null $credentialFields
     */
    public function provide(?array $credentialFields, SwagMigrationConnectionEntity $connection): ?string
    {
        return null;
    }
}
