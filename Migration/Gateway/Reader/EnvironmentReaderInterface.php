<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface EnvironmentReaderInterface
{
    public function read(MigrationContextInterface $migrationContext): array;
}
