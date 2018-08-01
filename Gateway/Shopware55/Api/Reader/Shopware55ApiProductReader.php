<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiProductReader
{
    public function read(Client $apiClient, int $offset, int $limit): array
    {
        /** @var GuzzleResponse $result */
        $result = $apiClient->get('SwagMigrationProducts', ['query' => ['offset' => $offset, 'limit' => $limit]]);

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api Products');
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
