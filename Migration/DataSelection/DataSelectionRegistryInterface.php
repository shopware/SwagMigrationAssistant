<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use SwagMigrationNext\Migration\MigrationContextInterface;

interface DataSelectionRegistryInterface
{
    public function getDataSelections(MigrationContextInterface $migrationContext): DataSelectionCollection;
}
