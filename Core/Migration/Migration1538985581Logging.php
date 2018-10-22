<?php declare(strict_types=1);

namespace SwagMigrationNext\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1538985581Logging extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1538985581;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE `swag_migration_logging` (
    `id`            BINARY(16)   NOT NULL,
    `tenant_id`     BINARY(16)   NOT NULL,
    `run_id`        BINARY(16)   NOT NULL,
    `run_tenant_id` BINARY(16)   NOT NULL,
    `type`          VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `log_entry`     LONGTEXT     NOT NULL,
    `created_at`    DATETIME(3)  NOT NULL,
    `updated_at`    DATETIME(3),
    PRIMARY KEY (`id`, `tenant_id`),
    CHECK (JSON_VALID(`message`))
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
