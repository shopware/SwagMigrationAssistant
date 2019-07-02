<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface TableCountReaderInterface
{
    public function readTotals(MigrationContextInterface $migrationContext): array;
}
