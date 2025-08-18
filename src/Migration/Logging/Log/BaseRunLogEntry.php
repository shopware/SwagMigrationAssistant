<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
abstract class BaseRunLogEntry implements LogEntryInterface
{
    public function __construct(
        protected string $runId,
        protected string $profileName,
        protected string $gatewayName,
    ) {
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }
}
