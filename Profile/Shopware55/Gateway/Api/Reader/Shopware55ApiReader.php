<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ReaderInterface;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\Shopware55DataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiReader implements ReaderInterface
{
    /**
     * @throws GatewayReadException
     */
    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        /** @var Shopware55DataSet $dataSet */
        $dataSet = $migrationContext->getDataSet();

        if (empty($dataSet->getApiRoute())) {
            throw new GatewayReadException('No endpoint for entity ' . $dataSet::getEntity() . ' available.');
        }

        $queryParams = [
            'offset' => $migrationContext->getOffset(),
            'limit' => $migrationContext->getLimit(),
        ];

        $queryParams = array_merge($queryParams, $dataSet->getExtraQueryParameters());

        $client = ConnectionFactory::createApiClient($migrationContext);

        /** @var GuzzleResponse $result */
        $result = $client->get(
            $dataSet->getApiRoute(),
            [
                'query' => $queryParams,
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api ' . $dataSet::getEntity());
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
