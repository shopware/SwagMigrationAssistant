<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface TableReaderFactoryInterface
{
    public function create(MigrationContextInterface $migrationContext): ?TableReaderInterface;
}
