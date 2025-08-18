<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

#[Package('fundamentals@after-sales')]
class Migration1754897550AddRequiredFieldsToMigrationLogs extends MigrationStep
{
    public const MIGRATION_LOGGING_TABLE = 'swag_migration_logging';

    public const REQUIRED_FIELDS = [
        'id' => 'BINARY(16) NOT NULL',
        'run_id' => null,
        'level' => null,
        'code' => null,
        'profile_name' => 'VARCHAR(255) NOT NULL',
        'gateway_name' => 'VARCHAR(255) NOT NULL',
        'user_fixable' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'auto_increment' => null,
        'created_at' => null,
        'updated_at' => null,
    ];

    public function getCreationTimestamp(): int
    {
        return 1754897550;
    }

    /**
     * @throws \Throwable
     */
    public function update(Connection $connection): void
    {
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::MIGRATION_LOGGING_TABLE])) {
            return;
        }

        $this->dropForeignKeyIfExists($connection, self::MIGRATION_LOGGING_TABLE, 'fk.swag_migration_logging.run_id');
        $this->dropIndexIfExists($connection, self::MIGRATION_LOGGING_TABLE, 'idx.swag_migration_logging.run_id_code');
        $this->dropConstraintIfExists($connection, 'json.swag_migration_logging.log_entry');

        $this->dropObsoleteColumns($connection, $schemaManager);
        $this->addOrModifyRequiredColumns($connection, $schemaManager);
        $this->ensureRelations($connection, $schemaManager);
    }

    /**
     * @param AbstractSchemaManager<MySQLPlatform> $schemaManager
     */
    private function dropObsoleteColumns(Connection $connection, AbstractSchemaManager $schemaManager): void
    {
        $columns = $schemaManager->listTableColumns(self::MIGRATION_LOGGING_TABLE);

        foreach ($columns as $column) {
            if (!\array_key_exists($column->getName(), self::REQUIRED_FIELDS)) {
                $connection->executeStatement(
                    \sprintf(
                        'ALTER TABLE `%s` DROP COLUMN `%s`;',
                        self::MIGRATION_LOGGING_TABLE,
                        $column->getName()
                    )
                );
            }
        }
    }

    /**
     * @param AbstractSchemaManager<MySQLPlatform> $schemaManager
     */
    private function addOrModifyRequiredColumns(Connection $connection, AbstractSchemaManager $schemaManager): void
    {
        $columns = $schemaManager->listTableColumns(self::MIGRATION_LOGGING_TABLE);

        foreach (self::REQUIRED_FIELDS as $name => $type) {
            if ($type === null) {
                continue;
            }

            if (!isset($columns[$name])) {
                $connection->executeStatement(
                    \sprintf(
                        'ALTER TABLE `%s` ADD COLUMN `%s` %s;',
                        self::MIGRATION_LOGGING_TABLE,
                        $name,
                        $type
                    )
                );
            } else {
                $connection->executeStatement(
                    \sprintf(
                        'ALTER TABLE `%s` MODIFY COLUMN `%s` %s;',
                        self::MIGRATION_LOGGING_TABLE,
                        $name,
                        $type
                    )
                );
            }
        }
    }

    /**
     * @param AbstractSchemaManager<MySQLPlatform> $schemaManager
     */
    private function ensureRelations(Connection $connection, AbstractSchemaManager $schemaManager): void
    {
        // ensure primary key and index
        $indexes = $schemaManager->listTableIndexes(self::MIGRATION_LOGGING_TABLE);

        if (isset($indexes['primary'])) {
            $connection->executeStatement(
                \sprintf(
                    'ALTER TABLE `%s` DROP PRIMARY KEY;',
                    self::MIGRATION_LOGGING_TABLE
                )
            );
        }

        $connection->executeStatement(
            \sprintf(
                'ALTER TABLE `%s` ADD PRIMARY KEY (`id`);',
                self::MIGRATION_LOGGING_TABLE
            )
        );

        $this->dropIndexIfExists(
            $connection,
            self::MIGRATION_LOGGING_TABLE,
            'idx.run_id'
        );
        $connection->executeStatement(
            \sprintf(
                'ALTER TABLE `%s` ADD INDEX `idx.run_id` (`run_id`);',
                self::MIGRATION_LOGGING_TABLE
            )
        );

        // ensure foreign key constraint
        $connection->executeStatement(
            \sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `fk.swag_migration_logging.run_id` FOREIGN KEY (`run_id`) REFERENCES `swag_migration_run` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;',
                self::MIGRATION_LOGGING_TABLE
            )
        );
    }

    private function dropConstraintIfExists(Connection $connection, string $constraintName): void
    {
        try {
            $connection->executeStatement(
                \sprintf(
                    'ALTER TABLE `%s` DROP CONSTRAINT `%s`;',
                    self::MIGRATION_LOGGING_TABLE,
                    $constraintName
                )
            );
        } catch (Exception) {
        }
    }
}
