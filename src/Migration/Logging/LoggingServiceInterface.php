<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogEntry;

#[Package('fundamentals@after-sales')]
interface LoggingServiceInterface
{
    public function addLogEntry(MigrationLogEntry $logEntry): void;

    /**
     * @param array<array-key, mixed> $keys
     * @param callable(array-key $key, mixed|null $value): MigrationLogEntry $callback
     */
    public function addLogForEach(array $keys, callable $callback): void;

    public function saveLogging(Context $context): void;
}
