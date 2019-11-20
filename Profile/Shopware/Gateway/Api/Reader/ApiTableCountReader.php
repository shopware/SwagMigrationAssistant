<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingQueryStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\Logging\Log\CannotReadEntityCountLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableCountReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiTableCountReader implements TableCountReaderInterface
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

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $dataSets = $this->dataSetRegistry->getDataSets($migrationContext);
//        $countingInformation = $this->getCountingInformation($dataSets);

        $client = $this->connectionFactory->createApiClient($migrationContext);
        /** @var GuzzleResponse $result */
        $result = $client->get(
            'SwagMigrationTotals'
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware Api table count.');
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            return [];
        }

        if (count($arrayResult['data']['exceptions']) > 0) {
            $this->logExceptions($arrayResult['data']['exceptions'], $migrationContext, $context);
        }

        return $this->prepareTotals($arrayResult['data']['totals']);
    }

//    /**
//     * @param DataSet[] $dataSets
//     */
//    private function getCountingInformation(array $dataSets): array
//    {
//        $countingInformation = [];
//
//        foreach ($dataSets as $dataSet) {
//            if ($dataSet->getCountingInformation() !== null) {
//                $info = $dataSet->getCountingInformation();
//                $queryData = [
//                    'entity' => $dataSet::getEntity(),
//                    'queryRules' => [],
//                ];
//
//                $queries = $info->getQueries();
//                /** @var CountingQueryStruct $queryStruct */
//                foreach ($queries as $queryStruct) {
//                    $queryData['queryRules'][] = [
//                        'table' => $queryStruct->getTableName(),
//                        'condition' => $queryStruct->getCondition(),
//                    ];
//                }
//                $countingInformation[] = $queryData;
//            }
//        }
//
//        return $countingInformation;
//    }

    /**
     * @return TotalStruct[]
     */
    private function prepareTotals(array $result): array
    {
        $totals = [];
        foreach ($result as $key => $tableResult) {
            $totals[$key] = new TotalStruct($key, $tableResult);
        }

        return $totals;
    }

    private function logExceptions(array $exceptionArray, MigrationContextInterface $migrationContext, Context $context): void
    {
        foreach ($exceptionArray as $exception) {
            $this->loggingService->addLogEntry(new CannotReadEntityCountLog(
                $migrationContext->getRunUuid(),
                $exception['entity'],
                $exception['table'],
                $exception['condition'],
                $exception['code'],
                $exception['message']
            ));
        }

        $this->loggingService->saveLogging($context);
    }
}
