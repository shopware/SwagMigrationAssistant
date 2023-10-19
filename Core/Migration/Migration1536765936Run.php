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
class Migration1536765936Run extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765936;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_migration_run` (
    `id`                      BINARY(16)    NOT NULL,
    `connection_id`           BINARY(16),
    `environment_information` LONGTEXT,
    `premapping`              LONGTEXT,
    `progress`                LONGTEXT,
    `user_id`                 VARCHAR(255),
    `access_token`            VARCHAR(255),
    `status`                  VARCHAR(255)  NOT NULL,
    `created_at`              DATETIME(3)   NOT NULL,
    `updated_at`              DATETIME(3),
    PRIMARY KEY (`id`),
    CONSTRAINT `json.swag_migration_run.environment_information` CHECK (JSON_VALID(`environment_information`)),
    CONSTRAINT `json.swag_migration_run.premapping` CHECK (JSON_VALID(`premapping`)),
    CONSTRAINT `json.swag_migration_run.progress` CHECK (JSON_VALID(`progress`)),
    CONSTRAINT `fk.swag_migration_run.connection_id` FOREIGN KEY (`connection_id`) REFERENCES `swag_migration_connection`(`id`)
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
