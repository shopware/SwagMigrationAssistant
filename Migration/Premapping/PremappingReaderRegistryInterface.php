<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Premapping;

use SwagMigrationNext\Migration\MigrationContextInterface;

interface PremappingReaderRegistryInterface
{
    /**
     * @param string[] $dataSelectionIds
     *
     * @return PremappingReaderInterface[]
     */
    public function getPremappingReaders(MigrationContextInterface $migrationContext, array $dataSelectionIds): array;
}
