<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Fingerprint\Provider;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;

#[Package('fundamentals@after-sales')]
interface MigrationFingerprintProviderInterface
{
    public static function supports(string $profileName): bool;

    /**
     * @param array<string, mixed>|null $credentialFields
     */
    public function provide(?array $credentialFields, SwagMigrationConnectionEntity $connection): ?string;
}
