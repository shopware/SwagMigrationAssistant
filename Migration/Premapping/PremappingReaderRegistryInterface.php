<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Premapping;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface PremappingReaderRegistryInterface
{
    /**
     * @param string[] $dataSelectionIds
     *
     * @return PremappingReaderInterface[]
     */
    public function getPremappingReaders(MigrationContextInterface $migrationContext, array $dataSelectionIds): array;
}
