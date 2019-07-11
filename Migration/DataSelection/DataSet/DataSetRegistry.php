<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

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
    public function getDataSets(MigrationContextInterface $migrationContext): array
    {
        $resultSet = [];
        foreach ($this->dataSets as $dataSet) {
            if ($dataSet->supports($migrationContext)) {
                $resultSet[] = $dataSet;
            }
        }

        return $resultSet;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataSet(MigrationContextInterface $migrationContext, string $entity): DataSet
    {
        foreach ($this->dataSets as $dataSet) {
            if ($dataSet->supports($migrationContext) && $dataSet::getEntity() === $entity) {
                return $dataSet;
            }
        }

        throw new DataSetNotFoundException($entity);
    }
}
