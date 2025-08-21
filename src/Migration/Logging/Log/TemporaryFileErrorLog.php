<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Logging\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractSwagMigrationLogEntry;

#[Package('fundamentals@after-sales')]
readonly class TemporaryFileErrorLog extends AbstractSwagMigrationLogEntry
{
    public function isUserFixable(): bool
    {
        return false;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_ERROR;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION__TEMPORARY_FILE_COULD_NOT_BE_CREATED';
    }
}
