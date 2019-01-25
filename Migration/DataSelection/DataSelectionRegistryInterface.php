<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use SwagMigrationNext\Migration\MigrationContext;

interface DataSelectionRegistryInterface
{
    public function getDataSelections(MigrationContext $migrationContext): DataSelectionCollection;
}
