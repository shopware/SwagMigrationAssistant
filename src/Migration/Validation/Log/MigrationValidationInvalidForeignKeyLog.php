<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Validation\Log;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\AbstractMigrationLogEntry;

#[Package('fundamentals@after-sales')]
readonly class MigrationValidationInvalidForeignKeyLog extends AbstractMigrationLogEntry
{
    public function isUserFixable(): bool
    {
        return true;
    }

    public function getLevel(): string
    {
        return self::LOG_LEVEL_WARNING;
    }

    public function getCode(): string
    {
        return 'SWAG_MIGRATION_VALIDATION_INVALID_FOREIGN_KEY';
    }
}
