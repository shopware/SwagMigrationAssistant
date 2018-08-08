<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api;

use GuzzleHttp\Client;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\GatewayInterface;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiAssetReader;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiCategoryReader;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiCustomerReader;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiProductReader;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderNotFoundException;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiTranslationReader;

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

    public function read(string $entityName, int $offset, int $limit): array
    {
        $apiClient = new Client([
            'base_uri' => $this->endpoint . '/api/',
            'auth' => [$this->apiUser, $this->apiKey, 'digest'],
        ]);

        switch ($entityName) {
            case ProductDefinition::getEntityName():
                $reader = new Shopware55ApiProductReader($offset, $limit);
                break;

            case CategoryDefinition::getEntityName():
                $reader = new Shopware55ApiCategoryReader($offset, $limit);
                break;

            case 'translation':
                $reader = new Shopware55ApiTranslationReader($offset, $limit);
                break;

            case MediaDefinition::getEntityName():
                $reader = new Shopware55ApiAssetReader($offset, $limit);
                break;

            case CustomerDefinition::getEntityName():
                $reader = new Shopware55ApiCustomerReader($offset, $limit);
                break;

            default:
                throw new Shopware55ApiReaderNotFoundException($entityName);
        }

        return $reader->read($apiClient);
    }
}
