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

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1757598733AddMigrationFixesTable extends MigrationStep
{
    public const MIGRATION_FIXES_TABLE = 'swag_migration_fix';

    public const FIELDS = [
        'id' => 'BINARY(16) NOT NULL',
        'connection_id' => 'BINARY(16) NOT NULL',
        'value' => 'JSON NOT NULL',
        'path' => 'VARCHAR(255) NOT NULL',
        'entity_name' => 'VARCHAR(255) NULL',
        'entity_id' => 'BINARY(16) NULL',
        'created_at' => 'DATETIME(3) NOT NULL',
        'updated_at' => 'DATETIME(3) NULL',
    ];

    public function getCreationTimestamp(): int
    {
        return 1757598733;
    }

    public function update(Connection $connection): void
    {
        $columns = [];

        foreach (self::FIELDS as $name => $definition) {
            $columns[] = "`$name` $definition";
        }

        $sql = \sprintf('
            CREATE TABLE IF NOT EXISTS `%s` (
                %s,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.swag_migration_fix.connection_id` FOREIGN KEY (`connection_id`) REFERENCES `swag_migration_connection` (`id`) ON DELETE CASCADE,
                INDEX `idx.entity_id` (`entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ', self::MIGRATION_FIXES_TABLE, implode(', ', $columns));

        $connection->executeStatement($sql);
    }
}
