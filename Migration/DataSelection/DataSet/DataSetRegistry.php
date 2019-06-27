<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use SwagMigrationAssistant\Exception\DataSetNotFoundException;

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
    public function getDataSets(string $profileName): array
    {
        $resultSet = [];
        foreach ($this->dataSets as $dataSet) {
            if ($dataSet->supports($profileName)) {
                $resultSet[] = $dataSet;
            }
        }

        return $resultSet;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSet(string $profileName, string $entity): DataSet
    {
        foreach ($this->dataSets as $dataSet) {
            if ($dataSet->supports($profileName) && $dataSet::getEntity() === $entity) {
                return $dataSet;
            }
        }

        throw new DataSetNotFoundException($entity);
    }
}
