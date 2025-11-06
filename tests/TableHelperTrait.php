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

    protected function dropTable(Connection $connection, string $table): void
    {
        $sql = \sprintf('DROP TABLE IF EXISTS `%s`', $table);
        $connection->executeStatement($sql);
    }

    protected function dropColumn(Connection $connection, string $table, string $columnName): void
    {
        if (!$this->columnExists($connection, $table, $columnName)) {
            return;
        }

        $connection->executeStatement(\sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', $table, $columnName));
    }

    protected function dropIndex(Connection $connection, string $table, string $indexName): void
    {
        if (!$this->indexExists($connection, $table, $indexName)) {
            return;
        }

        $sql = \sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName);

        $connection->executeStatement($sql);
    }
}
