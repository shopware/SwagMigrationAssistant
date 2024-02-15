<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection\DataSet;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class DataSetRegistry implements DataSetRegistryInterface
{
    /**
     * @param DataSet[] $dataSets
     */
    public function __construct(private readonly iterable $dataSets)
    {
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

        throw MigrationException::dataSetNotFound($dataSetName);
    }
}
