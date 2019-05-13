<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use SwagMigrationAssistant\Exception\DataSetNotFoundException;

interface DataSetRegistryInterface
{
    /**
     * @throws DataSetNotFoundException
     */
    public function getDataSet(string $profileName, string $dataSetName): DataSet;
}
