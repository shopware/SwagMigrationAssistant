<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Shopware\Core\Content\Product\ProductDefinition;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiProductReader implements Shopware55ApiReaderInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function read(Client $apiClient): array
    {
        /** @var GuzzleResponse $result */
        $result = $apiClient->get('SwagMigrationProducts');

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api Products');
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
