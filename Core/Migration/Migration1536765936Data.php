<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536765936Data extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765936;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_data` (
    `id`            BINARY(16)  NOT NULL,
    `tenant_id`     BINARY(16)  NOT NULL,
    `run_id`        BINARY(16)  NOT NULL,
    `run_tenant_id` BINARY(16)  NOT NULL,
    `entity`        VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `raw`           LONGTEXT,
    `converted`     LONGTEXT,
    `unmapped`      LONGTEXT,
    `written`       TINYINT(1)  NOT NULL DEFAULT '0',
    `created_at`    DATETIME(3) NOT NULL,
    `updated_at`    DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    KEY indexData (`entity`, `run_id`),
    CHECK (JSON_VALID(`raw`)),
    CHECK (JSON_VALID(`converted`)),
    CHECK (JSON_VALID(`unmapped`)),
    CONSTRAINT `fk_swag_migration_run.run_id` FOREIGN KEY (`run_id`, `run_tenant_id`) REFERENCES `swag_migration_run` (`id`, `tenant_id`) ON DELETE RESTRICT ON UPDATE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeQuery($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
