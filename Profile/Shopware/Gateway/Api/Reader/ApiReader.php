<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShopwareDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiReader implements ReaderInterface
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return false;
    }

    /**
     * @throws GatewayReadException
     */
    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        /** @var ShopwareDataSet $dataSet */
        $dataSet = $migrationContext->getDataSet();

        if (empty($dataSet->getApiRoute())) {
            throw new GatewayReadException('No endpoint for entity ' . $dataSet::getEntity() . ' available.');
        }

        $queryParams = [
            'offset' => $migrationContext->getOffset(),
            'limit' => $migrationContext->getLimit(),
        ];

        $queryParams = array_merge($queryParams, $dataSet->getExtraQueryParameters());

        $client = $this->connectionFactory->createApiClient($migrationContext);

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

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        return null;
    }
}
