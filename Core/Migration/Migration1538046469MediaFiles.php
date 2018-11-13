<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1538046469MediaFiles extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1538046469;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_media_file` (
    `id`              BINARY(16)  NOT NULL,
    `tenant_id`       BINARY(16)  NOT NULL,
    `run_id`          BINARY(16)  NOT NULL,
    `run_tenant_id`   BINARY(16)  NOT NULL,
    `uri`             LONGTEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `file_size`       int(11) UNSIGNED NOT NULL,
    `media_id`        BINARY(16)  NOT NULL,
    `written`         TINYINT(1) NOT NULL DEFAULT '0',
    `downloaded`      TINYINT(1) NOT NULL DEFAULT '0',
    `created_at`      DATETIME(3) NOT NULL,
    `updated_at`      DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    KEY indexMedia (`media_id`, `run_id`, `run_tenant_id`),
    KEY indexWritten (`written`, `run_id`, `run_tenant_id`),
    KEY indexDownloaded (`downloaded`, `run_id`, `run_tenant_id`),
    CONSTRAINT `fk_swag_migration_file.run_id` FOREIGN KEY (`run_id`, `run_tenant_id`) REFERENCES `swag_migration_run` (`id`, `tenant_id`) ON DELETE RESTRICT ON UPDATE CASCADE
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
