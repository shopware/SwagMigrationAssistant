<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface DataSetRegistryInterface
{
    /**
     * @return DataSet[]
     */
    public function getDataSets(MigrationContextInterface $migrationContext): array;

    /**
     * @throws DataSetNotFoundException
     */
    public function getDataSet(MigrationContextInterface $migrationContext, string $dataSetName): DataSet;
}
