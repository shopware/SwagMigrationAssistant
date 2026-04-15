<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration;

use Shopware\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 */
#[Package('fundamentals@after-sales')]
final readonly class MigrationConfiguration
{
    /**
     * @internal
     */
    public function __construct(
        public int $migrationLogBufferSize = 50,
        public int $migrationLogExceptionTraceItemLimit = 10,
        public int $migrationDefaultBatchSize = 100,
        public int $migrationMediaProcessingBatchSize = 10,
        public int $migrationDefaultExceptionThreshold = 3,
        public int $migrationDefaultFetchSize = 50,
        public int $migrationRequestTimeout = 15,
    ) {
    }
}
