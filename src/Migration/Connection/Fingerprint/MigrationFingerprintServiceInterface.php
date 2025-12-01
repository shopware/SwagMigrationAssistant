<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Fingerprint;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;

#[Package('fundamentals@after-sales')]
interface MigrationFingerprintServiceInterface
{
    /**
     * @param array<string, mixed>|null $credentialFields
     */
    public function generate(?array $credentialFields, SwagMigrationConnectionEntity $connection): ?string;

    public function check(?string $fingerprint, Context $context, ?string $excludeConnectionId): bool;
}
