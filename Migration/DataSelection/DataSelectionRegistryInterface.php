<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContextInterface;

interface DataSelectionRegistryInterface
{
    public function getDataSelections(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation): DataSelectionCollection;

    public function getDataSelectionsByIds(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation, array $ids): DataSelectionCollection;
}
