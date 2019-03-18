<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection\DataSet;

use SwagMigrationNext\Exception\DataSetNotFoundException;

interface DataSetRegistryInterface
{
    /**
     * @throws DataSetNotFoundException
     */
    public function getDataSet(string $profileName, string $dataSetName): DataSet;
}
