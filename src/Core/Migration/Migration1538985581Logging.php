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
class Migration1538985581Logging extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1538985581;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `swag_migration_logging` (
    `id`            BINARY(16)   NOT NULL,
    `run_id`        BINARY(16),
    `type`          VARCHAR(255) NOT NULL,
    `log_entry`     LONGTEXT     NOT NULL,
    `created_at`    DATETIME(3)  NOT NULL,
    `updated_at`    DATETIME(3),
    PRIMARY KEY (`id`),
    CONSTRAINT `json.swag_migration_logging.log_entry` CHECK (JSON_VALID(`log_entry`))
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
