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
class Migration1538046469MediaFile extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1538046469;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_migration_media_file` (
    `id`              BINARY(16)  NOT NULL,
    `run_id`          BINARY(16)  NOT NULL,
    `entity`          LONGTEXT    NOT NULL,
    `uri`             LONGTEXT    NOT NULL,
    `file_name`       LONGTEXT    NOT NULL,
    `file_size`       int(11)     UNSIGNED NOT NULL,
    `media_id`        BINARY(16)  NOT NULL,
    `written`         TINYINT(1)  NOT NULL DEFAULT '0',
    `processed`       TINYINT(1)  NOT NULL DEFAULT '0',
    `process_failure` TINYINT(1)  NOT NULL DEFAULT '0',
    `created_at`      DATETIME(3) NOT NULL,
    `updated_at`      DATETIME(3),
    PRIMARY KEY (`id`),
    KEY `idx.swag_migration_media_file.media_id__run_id` (`media_id`, `run_id`),
    KEY `idx.swag_migration_media_file.written__run_id` (`written`, `run_id`),
    KEY `idx.swag_migration_media_file.processed__run_id` (`processed`, `run_id`),
    CONSTRAINT `fk.swag_migration_media_file.run_id` FOREIGN KEY (`run_id`) REFERENCES `swag_migration_run` (`id`)
      ON DELETE RESTRICT
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
