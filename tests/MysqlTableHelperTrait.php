<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test;

use Doctrine\DBAL\Connection;

trait MysqlTableHelperTrait {
    protected function columnExists(Connection $connection, string $table, string $column): bool
    {
        $exists = $connection->fetchOne(
            'SHOW COLUMNS FROM `' . $table . '` WHERE `Field` LIKE :column',
            ['column' => $column]
        );

        return !empty($exists);
    }

    protected function indexExists(Connection $connection, string $table, string $indexName): bool
    {
        $exists = $connection->fetchAllAssociative('SHOW INDEX FROM `swag_migration_logging` WHERE Key_name = :indexName', ['indexName' => $indexName]);

        return !empty($exists);
    }
}