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

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class Migration1754897550AddFieldsToMigrationLogs extends MigrationStep
{
    public const MIGRATION_LOGGING_TABLE = 'swag_migration_logging';

    public const REQUIRED_FIELDS = [
        'id' => 'BINARY(16) NOT NULL',
        'run_id' => 'BINARY(16) NOT NULL',
        'profile_name' => 'VARCHAR(64) NOT NULL',
        'gateway_name' => 'VARCHAR(64) NOT NULL',
        'level' => 'VARCHAR(64) NOT NULL',
        'code' => 'VARCHAR(255) NOT NULL',
        'user_fixable' => 'TINYINT(1) NOT NULL DEFAULT 0',
    ];

    public const OPTIONAL_FIELDS = [
        'entity_name' => 'VARCHAR(64) NULL',
        'entity_id' => 'BINARY(16) NULL',
        'field_name' => 'VARCHAR(64) NULL',
        'field_source_path' => 'VARCHAR(255) NULL',
        'source_data' => 'JSON NULL',
        'converted_data' => 'JSON NULL',
        'exception_message' => 'LONGTEXT NULL',
        'exception_trace' => 'JSON NULL',
    ];

    public const SYSTEM_FIELDS = [
        'auto_increment' => 'BIGINT UNSIGNED AUTO_INCREMENT UNIQUE',
        'created_at' => 'DATETIME(3) NOT NULL',
        'updated_at' => 'DATETIME(3) NULL',
    ];

    public const FIELDS_TO_DROP = [
        'title',
        'description',
        'parameters',
        'title_snippet',
        'description_snippet',
        'entity',
        'source_id',
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

        $this->dropObsoleteColumns($connection);
        $this->addOrModifyColumns($connection, $schemaManager);
        $this->ensureRelations($connection, $schemaManager);
    }

    private function dropObsoleteColumns(Connection $connection): void
    {
        foreach (self::FIELDS_TO_DROP as $column) {
            $this->dropColumnIfExists($connection, 'swag_migration_logging', $column);
        }
    }

    /**
     * @param AbstractSchemaManager<MySQLPlatform> $schemaManager
     */
    private function addOrModifyColumns(Connection $connection, AbstractSchemaManager $schemaManager): void
    {
        $columns = $schemaManager->listTableColumns(self::MIGRATION_LOGGING_TABLE);

        $orderedFields = array_merge(
            self::REQUIRED_FIELDS,
            self::OPTIONAL_FIELDS,
            self::SYSTEM_FIELDS
        );

        foreach ($orderedFields as $name => $type) {
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

        // ensure entity_id index
        if (!$this->indexExists($connection, self::MIGRATION_LOGGING_TABLE, 'idx.entity_id')) {
            $connection->executeStatement(
                \sprintf(
                    'ALTER TABLE `%s` ADD INDEX `idx.entity_id` (`entity_id`);',
                    self::MIGRATION_LOGGING_TABLE
                )
            );
        }

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
