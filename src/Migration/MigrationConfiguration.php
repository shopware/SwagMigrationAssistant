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
        public int $MIGRATION_LOG_BUFFER_SIZE = 50,
        public int $MIGRATION_LOG_EXCEPTION_TRACE_ITEM_LIMIT = 10,
        public int $MIGRATION_DEFAULT_BATCH_SIZE = 250,
        public int $MIGRATION_MEDIA_PROCESSING_BATCH_SIZE = 10,
        public int $MIGRATION_DEFAULT_EXCEPTION_THRESHOLD = 3,
        public int $MIGRATION_DEFAULT_FETCH_SIZE = 50,
        public int $MIGRATION_CONNECTION_TIMEOUT = 15,
    ) {
    }
}
