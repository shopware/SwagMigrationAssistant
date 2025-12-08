<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1759000000AddIsResettingChecksumsToSetting extends MigrationStep
{
    public const TABLE = 'swag_migration_general_setting';

    public const COLUMN = 'is_resetting_checksums';

    public function getCreationTimestamp(): int
    {
        return 1759000000;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn(
            connection: $connection,
            table: self::TABLE,
            column: self::COLUMN,
            type: 'TINYINT(1)',
            nullable: false,
            default: '0'
        );
    }
}
