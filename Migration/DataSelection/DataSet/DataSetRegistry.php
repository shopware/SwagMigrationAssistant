<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class DataSetRegistry implements DataSetRegistryInterface
{
    /**
     * @var DataSet[]
     */
    private $dataSets;

    /**
     * @param DataSet[] $dataSets
     */
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
    public function getDataSet(MigrationContextInterface $migrationContext, string $dataSetName): DataSet
    {
        foreach ($this->dataSets as $dataSet) {
            if ($dataSet->supports($migrationContext) && $dataSet::getEntity() === $dataSetName) {
                return $dataSet;
            }
        }

        throw new DataSetNotFoundException($dataSetName);
    }
}
