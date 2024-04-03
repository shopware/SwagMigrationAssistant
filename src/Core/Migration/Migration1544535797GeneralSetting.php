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
class Migration1544535797GeneralSetting extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1544535797;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_migration_general_setting` (
    `id`                      BINARY(16)   NOT NULL,
    `selected_connection_id`  BINARY(16),
    `created_at`              DATETIME(3)  NOT NULL,
    `updated_at`              DATETIME(3),
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.swag_migration_general_setting.selected_connection_id` FOREIGN KEY (selected_connection_id)
      REFERENCES swag_migration_connection (id)
      ON DELETE SET NULL
      ON UPDATE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
