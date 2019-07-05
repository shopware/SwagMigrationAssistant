<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingInformationStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingQueryStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\TableCountReaderInterface;

class Shopware55LocalTableCountReader implements TableCountReaderInterface
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    public function __construct(
        ConnectionFactoryInterface $connectionFactory,
        DataSetRegistryInterface $dataSetRegistry
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->dataSetRegistry = $dataSetRegistry;
    }

    /**
     * @return TotalStruct[]
     */
    public function readTotals(MigrationContextInterface $migrationContext): array
    {
        $dataSets = $this->dataSetRegistry->getDataSets($migrationContext->getConnection()->getProfileName());
        $countingInformation = $this->getCountingInformation($dataSets);
        $connection = $this->connectionFactory->createDatabaseConnection($migrationContext);

        $totals = [];
        foreach ($countingInformation as $countingInfo) {
            $totalQueries = $countingInfo->getQueries();

            if ($totalQueries->count() === 0) {
                $totals[$countingInfo->getEntityName()] = new TotalStruct($countingInfo->getEntityName(), 0);

                continue;
            }

            $total = 0;
            /** @var CountingQueryStruct $queryStruct */
            foreach ($totalQueries as $queryStruct) {
                $query = $connection->createQueryBuilder();
                $query = $query->select('COUNT(*)')->from($queryStruct->getTableName());

                if ($queryStruct->getCondition()) {
                    $query->where($queryStruct->getCondition());
                }
                $total += (int) $query->execute()->fetchColumn();
            }

            $totals[$countingInfo->getEntityName()] = new TotalStruct($countingInfo->getEntityName(), $total);
        }

        return $totals;
    }

    /**
     * @param DataSet[] $dataSets
     *
     * @return CountingInformationStruct[]
     */
    private function getCountingInformation(array $dataSets): array
    {
        $countingInformation = [];

        foreach ($dataSets as $dataSet) {
            if ($dataSet->getCountingInformation() !== null) {
                $countingInformation[] = $dataSet->getCountingInformation();
            }
        }

        return $countingInformation;
    }
}
