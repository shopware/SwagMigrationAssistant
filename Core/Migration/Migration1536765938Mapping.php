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
class Migration1536765938Mapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765938;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_migration_mapping` (
    `id`              BINARY(16)  NOT NULL,
    `connection_id`   BINARY(16) NOT NULL,
    `entity`          VARCHAR(255),
    `old_identifier`  VARCHAR(255),
    `entity_uuid`     BINARY(16),
    `entity_value`  VARCHAR(255),
    `additional_data` LONGTEXT,
    `created_at`      DATETIME(3) NOT NULL,
    `updated_at`      DATETIME(3),
    KEY `idx.swag_migration_mapping.entity__old_identifier` (`entity`, `old_identifier`),
    CONSTRAINT `json.swag_migration_mapping.additional_data` CHECK (JSON_VALID(`additional_data`)),
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.swag_migration_mapping.connection_id` FOREIGN KEY (`connection_id`) REFERENCES `swag_migration_connection`(`id`)
      ON DELETE CASCADE
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
