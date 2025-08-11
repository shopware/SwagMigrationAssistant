<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
class EntityAlreadyExistsRunLog extends BaseRunLogEntry
{
    public function __construct(
        string $runId,
        string $profileName,
        string $gatewayName,
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
        return self::LOG_LEVEL_INFO;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION_%s_ENTITY_ALREADY_EXISTS';
    }
}
