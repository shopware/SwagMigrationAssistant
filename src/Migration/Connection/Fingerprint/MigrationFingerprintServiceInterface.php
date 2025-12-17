<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Connection\Fingerprint;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
interface MigrationFingerprintServiceInterface
{
    public function check(?string $fingerprint, Context $context, ?string $excludeConnectionId): bool;
}
