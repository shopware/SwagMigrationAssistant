<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1544535797GeneralSetting extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1544535797;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_general_setting` (
    `id`                    BINARY(16)   NOT NULL,
    `selected_profile_id`   BINARY(16),
    `created_at`            DATETIME(3)  NOT NULL,
    `updated_at`            DATETIME(3),
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_swag_migration_general_setting.selected_profile_id` FOREIGN KEY (selected_profile_id)
      REFERENCES swag_migration_profile (id)
      ON DELETE SET NULL
      ON UPDATE SET NULL
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->exec($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
