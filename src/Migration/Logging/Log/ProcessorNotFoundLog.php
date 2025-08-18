<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
class ProcessorNotFoundLog implements LogEntryInterface
{
    public function __construct(
        private readonly string $runId,
        private readonly string $profileName,
        private readonly string $gatewayName,
    ) {
    }

    public function isUserFixable(): bool
    {
        return false;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__PROCESSOR_NOT_FOUND';
    }

    public function getRunId(): string
    {
        return $this->runId;
    }
}
