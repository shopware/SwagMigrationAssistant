<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ReaderInterface
{
    /**
     * Reads data from source via the given gateway based on implementation
     */
    public function read(MigrationContextInterface $migrationContext, array $params = []): array;
}
