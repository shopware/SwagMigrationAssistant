<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\CountingQueryStruct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\TableCountReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiTableCountReader implements TableCountReaderInterface
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

    public function readTotals(MigrationContextInterface $migrationContext): array
    {
        $dataSets = $this->dataSetRegistry->getDataSets($migrationContext->getConnection()->getProfileName());
        $countingInformation = $this->getCountingInformation($dataSets);

        $client = $this->connectionFactory->createApiClient($migrationContext);
        /** @var GuzzleResponse $result */
        $result = $client->get(
            'SwagMigrationTotals',
            [
                'query' => [
                    'countInfos' => $countingInformation,
                ],
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api table count.');
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            return [];
        }

        return $this->prepareTotals($arrayResult['data']);
    }

    /**
     * @param DataSet[] $dataSets
     */
    private function getCountingInformation(array $dataSets): array
    {
        $countingInformation = [];

        foreach ($dataSets as $dataSet) {
            if ($dataSet->getCountingInformation() !== null) {
                $info = $dataSet->getCountingInformation();
                $queryData = [
                    'entity' => $dataSet::getEntity(),
                    'queryRules' => [],
                ];

                $queries = $info->getQueries();
                /** @var CountingQueryStruct $queryStruct */
                foreach ($queries as $queryStruct) {
                    $queryData['queryRules'][] = [
                        'table' => $queryStruct->getTableName(),
                        'condition' => $queryStruct->getCondition(),
                    ];
                }
                $countingInformation[] = $queryData;
            }
        }

        return $countingInformation;
    }

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
}
