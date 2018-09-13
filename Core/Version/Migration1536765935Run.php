<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Version;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1536765935Run extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536765935;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_run` (
    `id`         BINARY(16)   NOT NULL,
    `tenant_id`  BINARY(16)   NOT NULL,
    `profile`    VARCHAR(255) NOT NULL,
    `totals`     LONGTEXT,
    `created_at` DATETIME(3)  NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    CHECK (JSON_VALID(`totals`))
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
