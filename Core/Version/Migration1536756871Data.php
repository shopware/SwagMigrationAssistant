<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Version;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536756871Data extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536756871;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_data` (
    `id`         BINARY(16)  NOT NULL,
    `tenant_id`  BINARY(16)  NOT NULL,
    `profile`    VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `entity`     VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `raw`        LONGTEXT,
    `converted`  LONGTEXT,
    `unmapped`   LONGTEXT,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    KEY indexData (`entity`, `profile`, `created_at`),
    CHECK (JSON_VALID(`raw`)),
    CHECK (JSON_VALID(`converted`)),
    CHECK (JSON_VALID(`unmapped`))
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
