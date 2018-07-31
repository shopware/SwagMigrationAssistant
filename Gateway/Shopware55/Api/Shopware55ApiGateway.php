<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api;

use GuzzleHttp\Client;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiProductReader;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderNotFoundException;

class Shopware55ApiGateway implements GatewayInterface
{
    public const GATEWAY_TYPE = 'api';

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $apiUser;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct(
        string $endpoint,
        string $apiUser,
        string $apiKey
    ) {
        $this->endpoint = $endpoint;
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
    }

    public function read(string $entityName): array
    {
        $apiClient = new Client([
            'base_uri' => $this->endpoint . '/api/',
            'auth' => [$this->apiUser, $this->apiKey, 'digest'],
        ]);

        switch ($entityName) {
            case ProductDefinition::getEntityName():
                $reader = new Shopware55ApiProductReader();

                return $reader->read($apiClient);

            default:
                throw new Shopware55ApiReaderNotFoundException($entityName);
        }
    }
}
