<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536765935Run extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765935;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_run` (
    `id`                      BINARY(16)    NOT NULL,
    `profile_id`              BINARY(16),
    `totals`                  LONGTEXT,
    `environment_information` LONGTEXT,
    `additional_data`         LONGTEXT,
    `credential_fields`       LONGTEXT,
    `user_id`                 VARCHAR(255),
    `access_token`            VARCHAR(255),
    `status`                  VARCHAR(255)  NOT NULL,
    `created_at`              DATETIME(3)   NOT NULL,
    `updated_at`              DATETIME(3),
    CHECK (JSON_VALID(`totals`)),
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_swag_migration_run.profile_id` FOREIGN KEY (`profile_id`) REFERENCES `swag_migration_profile`(`id`)
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
