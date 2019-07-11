<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1538985581Logging extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1538985581;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_logging` (
    `id`                        BINARY(16)   NOT NULL,
    `level`                     VARCHAR(64)  NOT NULL,
    `code`                      VARCHAR(255) NOT NULL,
    `title`                     LONGTEXT     NOT NULL,
    `description`               LONGTEXT     NOT NULL,
    `description_arguments`     LONGTEXT     NOT NULL,
    `title_snippet`             TEXT     NOT NULL,
    `description_snippet`       TEXT     NOT NULL,
    `entity`                    VARCHAR(128),
    `source_id`                 VARCHAR(64),
    `run_id`                    BINARY(16),
    `created_at`                DATETIME(3)  NOT NULL,
    `updated_at`                DATETIME(3),
    PRIMARY KEY (`id`),
    KEY `idx.swag_migration_logging.run_id_level` (`run_id`, `level`),
    KEY `idx.swag_migration_logging.run_id_code` (`run_id`, `code`),
    KEY `idx.swag_migration_logging.run_id_entity` (`run_id`, `entity`),
    KEY `idx.swag_migration_logging.run_id_source_id` (`run_id`, `source_id`),
    CONSTRAINT `json.swag_migration_logging.log_entry` CHECK (JSON_VALID(`description_arguments`)),
    CONSTRAINT `fk.swag_migration_logging.run_id` FOREIGN KEY (`run_id`) REFERENCES `swag_migration_run` (`id`)
      ON DELETE RESTRICT
      ON UPDATE CASCADE
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
