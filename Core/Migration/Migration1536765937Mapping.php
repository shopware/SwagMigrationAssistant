<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536765937Mapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765937;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_mapping` (
    `id`              BINARY(16)  NOT NULL,
    `profile_id`      BINARY(16),
    `entity`          VARCHAR(255),
    `old_identifier`  VARCHAR(255),
    `entity_uuid`     BINARY(16),
    `additional_data` LONGTEXT,
    `created_at`      DATETIME(3) NOT NULL,
    `updated_at`      DATETIME(3),
    KEY indexMapping (`entity`, `old_identifier`),
    CHECK (JSON_VALID(`additional_data`)),
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_swag_migration_mapping.profile_id` FOREIGN KEY (`profile_id`) REFERENCES `swag_migration_profile`(`id`)
      ON DELETE SET NULL
      ON UPDATE NO ACTION
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
