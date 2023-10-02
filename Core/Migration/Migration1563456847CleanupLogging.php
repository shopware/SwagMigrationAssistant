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
class Migration1563456847CleanupLogging extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1563456847;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
DROP TABLE `swag_migration_logging`;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_migration_logging` (
    `id`                        BINARY(16)   NOT NULL,
    `level`                     VARCHAR(64)  NOT NULL,
    `code`                      VARCHAR(255) NOT NULL,
    `title`                     LONGTEXT     NOT NULL,
    `description`               LONGTEXT     NOT NULL,
    `parameters`                LONGTEXT     NOT NULL,
    `title_snippet`             VARCHAR(255) NOT NULL,
    `description_snippet`       VARCHAR(255) NOT NULL,
    `entity`                    VARCHAR(128),
    `source_id`                 VARCHAR(64),
    `run_id`                    BINARY(16),
    `created_at`                DATETIME(3)  NOT NULL,
    `updated_at`                DATETIME(3),
    PRIMARY KEY (`id`),
    KEY `idx.swag_migration_logging.run_id_code` (`run_id`, `code`),
    CONSTRAINT `json.swag_migration_logging.log_entry` CHECK (JSON_VALID(`parameters`)),
    CONSTRAINT `fk.swag_migration_logging.run_id` FOREIGN KEY (`run_id`) REFERENCES `swag_migration_run` (`id`)
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
