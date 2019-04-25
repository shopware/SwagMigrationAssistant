<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536765935Connection extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765935;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_connection` (
    `id`                    BINARY(16)   NOT NULL,
    `name`                  VARCHAR(255),
    `credential_fields`     LONGTEXT,
    `premapping`            LONGTEXT,
    `profile_id`            BINARY(16) NOT NULL,
    `created_at`            DATETIME(3)  NOT NULL,
    `updated_at`            DATETIME(3),
    PRIMARY KEY (`id`),
    CONSTRAINT `uniq.swag_migration_connection.name` UNIQUE (`name`),
    CONSTRAINT `json.swag_migration_connection.credential_fields` CHECK (JSON_VALID(`credential_fields`)),
    CONSTRAINT `json.swag_migration_connection.premapping` CHECK (JSON_VALID(`premapping`)),
    CONSTRAINT `fk.swag_migration_connection.profile_id` FOREIGN KEY (`profile_id`) REFERENCES `swag_migration_profile`(`id`)
      ON DELETE CASCADE
      ON UPDATE CASCADE
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
