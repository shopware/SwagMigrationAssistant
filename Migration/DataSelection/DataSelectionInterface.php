<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface DataSelectionInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool;

    public function getData(): DataSelectionStruct;

    /**
     * @return string[]
     */
    public function getEntityNames(): array;

    /**
     * @return string[]
     */
    public function getEntityNamesRequiredForCount(): array;
}
