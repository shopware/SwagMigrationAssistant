<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
enum MigrationProgressStatus: string
{
    case IDLE = 'idle';

    case FETCHING = 'fetching';

    case WRITING = 'writing';

    case MEDIA_PROCESSING = 'media-processing';

    case CLEANUP = 'cleanup';

    case INDEXING = 'indexing';

    case WAITING_FOR_APPROVE = 'waiting-for-approve';

    case ABORTING = 'aborting';

    case FINISHED = 'finished';

    case ABORTED = 'aborted';
}
