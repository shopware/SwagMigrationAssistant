<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api;

use GuzzleHttp\Client;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderRegistryInterface;

class Shopware55ApiGateway implements GatewayInterface
{
    public const GATEWAY_TYPE = 'api';

    /**
     * @var Shopware55ApiReaderRegistryInterface
     */
    private $apiReaderRegistry;

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
        Shopware55ApiReaderRegistryInterface $apiReaderRegistry,
        string $endpoint,
        string $apiUser,
        string $apiKey
    ) {
        $this->endpoint = $endpoint;
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->apiReaderRegistry = $apiReaderRegistry;
    }

    public function read(string $entityName): array
    {
        $apiClient = new Client([
            'base_uri' => $this->endpoint . '/api/',
            'auth' => [$this->apiUser, $this->apiKey, 'digest'],
        ]);

        $reader = $this->apiReaderRegistry->getReader($entityName);

        return $reader->read($apiClient);
    }
}
