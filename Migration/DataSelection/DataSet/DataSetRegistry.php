<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection\DataSet;

use SwagMigrationNext\Exception\DataSetNotFoundException;

class DataSetRegistry implements DataSetRegistryInterface
{
    /**
     * @var DataSet[]
     */
    private $dataSets;

    public function __construct(iterable $dataSets)
    {
        $this->dataSets = $dataSets;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSet(string $profileName, string $entity): DataSet
    {
        foreach ($this->dataSets as $dataSet) {
            if ($dataSet->supports($profileName, $entity)) {
                return $dataSet;
            }
        }

        throw new DataSetNotFoundException($entity);
    }
}
