<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

use SwagMigrationNext\Migration\MigrationContextInterface;

interface TableReaderFactoryInterface
{
    public function create(MigrationContextInterface $migrationContext): ?TableReaderInterface;
}
