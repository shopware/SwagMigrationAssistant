<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Migration\Profile\TableReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiTableReader implements TableReaderInterface
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function read(string $tableName): array
    {
        /** @var GuzzleResponse $result */
        $result = $this->client->get(
            'SwagMigrationDynamic',
            [
                'query' => [
                    'table' => $tableName,
                ],
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api ' . $tableName);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
