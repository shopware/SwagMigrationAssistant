<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\LogEntryInterface;

#[Package('services-settings')]
interface LoggingServiceInterface
{
    public function addLogEntry(LogEntryInterface $logEntry): void;

    public function saveLogging(Context $context): void;
}
