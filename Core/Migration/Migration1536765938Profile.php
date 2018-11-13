<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536765938Profile extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765938;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_profile` (
    `id`                BINARY(16)  NOT NULL,
    `tenant_id`         BINARY(16)  NOT NULL,
    `profile`           VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `gateway`           VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `credential_fields` LONGTEXT,
    `created_at`        DATETIME(3) NOT NULL,
    `updated_at`        DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    CHECK (JSON_VALID(`credential_fields`))
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
