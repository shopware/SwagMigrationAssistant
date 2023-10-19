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

#[Package('services-settings')]
class Migration1589448383AddIsResetColumnToSetting extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1589448383;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `swag_migration_general_setting` ADD `is_reset` TINYINT(1)  NOT NULL DEFAULT '0' AFTER `selected_connection_id`;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
