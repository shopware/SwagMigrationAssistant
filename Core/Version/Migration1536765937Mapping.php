<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Version;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536765937Mapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765937;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_mapping` (
    `id`              BINARY(16)  NOT NULL,
    `tenant_id`       BINARY(16)  NOT NULL,
    `profile`         VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `entity`          VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `old_identifier`  VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `entity_uuid`     BINARY(16),
    `additional_data` LONGTEXT,
    `created_at`      DATETIME(3) NOT NULL,
    `updated_at`      DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    KEY indexMapping (`entity`, `profile`, `old_identifier`),
    CHECK (JSON_VALID(`additional_data`))
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
