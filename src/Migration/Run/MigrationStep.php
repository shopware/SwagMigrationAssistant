<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
enum MigrationStep: string
{
    final public const MANUAL_STEPS = [
        self::ERROR_RESOLUTION,
        self::WAITING_FOR_APPROVE,
    ];

    final public const FINAL_STEPS = [
        self::FINISHED,
        self::ABORTED,
    ];

    case IDLE = 'idle';

    case FETCHING = 'fetching';

    case ERROR_RESOLUTION = 'error-resolution';

    case WRITING = 'writing';

    case MEDIA_PROCESSING = 'media-processing';

    case CLEANUP = 'cleanup';

    case INDEXING = 'indexing';

    case WAITING_FOR_APPROVE = 'waiting-for-approve';

    case ABORTING = 'aborting';

    case FINISHED = 'finished';

    case ABORTED = 'aborted';

    public function isRunning(): bool
    {
        return !\in_array(
            $this,
            self::FINAL_STEPS,
            true
        );
    }

    public function needsProcessor(): bool
    {
        return !\in_array(
            $this,
            self::MANUAL_STEPS,
            true
        );
    }
}
