<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\AddColumnTrait;

/**
 * @internal
 */
#[Package('after-sales')]
trait TableHelperTrait
{
    // Contains functions columnExists and addColumn
    use AddColumnTrait;

    protected function indexExists(Connection $connection, string $table, string $indexName): bool
    {
        $exists = $connection->fetchAssociative('SHOW INDEX FROM `' . $table . '` WHERE Key_name = :indexName', ['indexName' => $indexName]);

        return !empty($exists);
    }

    protected function columnExists(Connection $connection, string $table, string $column): bool
    {
        $exists = $connection->fetchOne('SHOW COLUMNS FROM `' . $table . '` LIKE :columnName', ['columnName' => $column]);

        return !empty($exists);
    }

    protected function tableExists(Connection $connection, string $table): bool
    {
        $exists = $connection->fetchOne('SHOW TABLES LIKE :tableName', ['tableName' => $table]);

        return !empty($exists);
    }

    protected function dropTableIfExists(Connection $connection, string $table): void
    {
        $sql = \sprintf('DROP TABLE IF EXISTS `%s`', $table);
        $connection->executeStatement($sql);
    }

    protected function dropColumnIfExists(Connection $connection, string $table, string $columnName): void
    {
        if (!$this->columnExists($connection, $table, $columnName)) {
            return;
        }

        $connection->executeStatement(\sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $table, $columnName));
    }

    protected function dropIndexIfExists(Connection $connection, string $table, string $indexName): void
    {
        if (!$this->indexExists($connection, $table, $indexName)) {
            return;
        }

        $sql = \sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName);

        $connection->executeStatement($sql);
    }

    protected function foreignKeyExists(Connection $connection, string $table, string $foreignKeyName): bool
    {
        $exists = $connection->fetchOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tableName AND CONSTRAINT_NAME = :constraintName AND REFERENCED_TABLE_NAME IS NOT NULL',
            [
                'tableName' => $table,
                'constraintName' => $foreignKeyName,
            ]
        );

        return !empty($exists);
    }

    protected function addForeignKey(
        Connection $connection,
        string $table,
        string $foreignKeyName,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete = 'CASCADE',
    ): void {
        if ($this->foreignKeyExists($connection, $table, $foreignKeyName)) {
            return;
        }

        $sql = \sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s',
            $table,
            $foreignKeyName,
            $column,
            $referencedTable,
            $referencedColumn,
            $onDelete
        );

        $connection->executeStatement($sql);
    }

    protected function dropForeignKeyIfExists(Connection $connection, string $table, string $foreignKeyName): void
    {
        if (!$this->foreignKeyExists($connection, $table, $foreignKeyName)) {
            return;
        }

        $sql = \sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $foreignKeyName);

        $connection->executeStatement($sql);
    }
}
