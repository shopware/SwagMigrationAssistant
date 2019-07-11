<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingInformationStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingQueryStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Logging\LogType;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableCountReaderInterface;

class LocalTableCountReader implements TableCountReaderInterface
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var DataSetRegistryInterface
     */
    private $dataSetRegistry;

    /**
     * @var LoggingService
     */
    private $loggingService;

    public function __construct(
        ConnectionFactoryInterface $connectionFactory,
        DataSetRegistryInterface $dataSetRegistry,
        LoggingService $loggingService
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->dataSetRegistry = $dataSetRegistry;
        $this->loggingService = $loggingService;
    }

    /**
     * @return TotalStruct[]
     */
    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $dataSets = $this->dataSetRegistry->getDataSets($migrationContext);
        $countingInformation = $this->getCountingInformation($dataSets);
        $connection = $this->connectionFactory->createDatabaseConnection($migrationContext);

        $totals = [];
        foreach ($countingInformation as $countingInfo) {
            $totalQueries = $countingInfo->getQueries();
            $entityName = $countingInfo->getEntityName();

            if ($totalQueries->count() === 0) {
                $totals[$entityName] = new TotalStruct($entityName, 0);

                continue;
            }

            $total = 0;
            /** @var CountingQueryStruct $queryStruct */
            foreach ($totalQueries as $queryStruct) {
                try {
                    $query = $connection->createQueryBuilder();
                    $query = $query->select('COUNT(*)')->from($queryStruct->getTableName());

                    if ($queryStruct->getCondition()) {
                        $query->where($queryStruct->getCondition());
                    }
                    $total += (int) $query->execute()->fetchColumn();
                } catch (\Exception $exception) {
                    $this->loggingService->addWarning(
                        $migrationContext->getRunUuid(),
                        LogType::COULD_NOT_READ_ENTITY_COUNT,
                        'Could not read entity count',
                        sprintf(
                            'Total count for entity %s could not be read. Make the the table %s exists in your source system and the optional condition "%s" is valid.',
                            $entityName,
                            $queryStruct->getTableName(),
                            $queryStruct->getCondition() ?? ''
                        ),
                        [
                            'exceptionCode' => $exception->getCode(),
                            'exceptionMessage' => $exception->getMessage(),
                            'entity' => $entityName,
                            'table' => $queryStruct->getTableName(),
                            'condition' => $queryStruct->getCondition(),
                        ]
                    );
                }
            }

            $totals[$entityName] = new TotalStruct($entityName, $total);
        }

        $this->loggingService->saveLogging($context);

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
