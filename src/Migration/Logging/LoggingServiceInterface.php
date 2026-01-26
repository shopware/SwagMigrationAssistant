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
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('fundamentals@after-sales')]
interface LoggingServiceInterface
{
    public function log(MigrationLogEntry $logEntry): void;

    public function flush(Context $context): void;

    public function setContext(?MigrationContextInterface $migrationContext, ?Context $context): void;

    /**
     * @param array<array-key, mixed> $keys
     * @param callable(array-key $key, mixed|null $value): MigrationLogEntry $callback
     */
    public function addLogForEach(array $keys, callable $callback): void;
}
