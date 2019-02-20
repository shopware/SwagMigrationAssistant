<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

interface TableReaderInterface
{
    /**
     * Reads data from source table via the given gateway based on implementation
     */
    public function read(string $tableName): array;
}
