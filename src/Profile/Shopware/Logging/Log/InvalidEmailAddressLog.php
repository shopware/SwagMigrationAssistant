<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\BaseRunLogEntry;

#[Package('fundamentals@after-sales')]
class InvalidEmailAddressLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $profileName,
        string $gatewayName,
        /** @phpstan-ignore property.onlyWritten */
        private readonly string $email,
    ) {
        parent::__construct(
            $runId,
            $profileName,
            $gatewayName,
        );
    }

    public function isUserFixable(): bool
    {
        return false;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__INVALID_EMAIL_ADDRESS';
    }
}
