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
class Migration1762436233RemoveMainMappingIdFromMigrationFix extends MigrationStep
{
    public const TABLE_NAME = 'swag_migration_fix';
    public const COLUMN_NAME = 'main_mapping_id';
    public const FOREIGN_KEY_NAME = 'fk.swag_migration_fix.main_mapping_id';

    public function getCreationTimestamp(): int
    {
        return 1762436233;
    }

    /**
     * @throws \Throwable
     */
    public function update(Connection $connection): void
    {
        $this->dropForeignKeyIfExists(
            $connection,
            self::TABLE_NAME,
            self::FOREIGN_KEY_NAME,
        );

        $this->dropColumnIfExists(
            $connection,
            self::TABLE_NAME,
            self::COLUMN_NAME,
        );
    }
}
