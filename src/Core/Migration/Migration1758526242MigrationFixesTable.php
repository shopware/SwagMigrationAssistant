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

#[Package('fundamentals@after-sales')]
class Migration1758526242MigrationFixesTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1758526242;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_migration_fixes` (
    `id` BINARY(16) NOT NULL,
    `migration_mapping_id` BINARY(16) NOT NULL,
    `fix` JSON NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`),
    CONSTRAINT `json.migration_fixes.fix` CHECK (JSON_VALID(`fix`)),
    CONSTRAINT `fk.swag_migration_fixes.migration_data_id` FOREIGN KEY (`migration_mapping_id`)
        REFERENCES `swag_migration_mapping` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }
}