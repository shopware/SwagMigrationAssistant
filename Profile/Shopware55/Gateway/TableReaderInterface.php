<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface TableReaderInterface
{
    /**
     * Reads data from source table via the given gateway based on implementation
     */
    public function read(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array;
}
