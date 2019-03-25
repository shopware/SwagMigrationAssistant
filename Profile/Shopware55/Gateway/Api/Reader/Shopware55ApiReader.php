<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Profile\ReaderInterface;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\Shopware55DataSet;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiReader implements ReaderInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var MigrationContextInterface
     */
    protected $migrationContext;

    /**
     * @var array
     */
    protected $routeMapping;

    public function __construct(Client $client, MigrationContextInterface $migrationContext)
    {
        $this->client = $client;
        $this->migrationContext = $migrationContext;
    }

    /**
     * @throws GatewayReadException
     */
    public function read(): array
    {
        /** @var Shopware55DataSet $dataSet */
        $dataSet = $this->migrationContext->getDataSet();

        if (empty($dataSet->getApiRoute())) {
            throw new GatewayReadException('No endpoint for entity ' . $dataSet::getEntity() . ' available.');
        }

        $queryParams = [
            'offset' => $this->migrationContext->getOffset(),
            'limit' => $this->migrationContext->getLimit(),
        ];

        $queryParams = array_merge($queryParams, $dataSet->getExtraQueryParameters());

        /** @var GuzzleResponse $result */
        $result = $this->client->get(
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
