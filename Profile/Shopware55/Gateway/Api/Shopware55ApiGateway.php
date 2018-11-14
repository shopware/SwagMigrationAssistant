<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api;

use GuzzleHttp\Client;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiAssetReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiCategoryReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiCustomerReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiOrderReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiProductReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReaderNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiTranslationReader;

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
        $apiClientOptions = [
            'base_uri' => $this->endpoint . '/api/',
            'auth' => [$this->apiUser, $this->apiKey, 'digest'],
            'verify' => false,
        ];
        $apiClient = new Client($apiClientOptions);

        switch ($entityName) {
            case ProductDefinition::getEntityName():
                $reader = new Shopware55ApiProductReader($offset, $limit);
                break;

            case CategoryDefinition::getEntityName():
                $reader = new Shopware55ApiCategoryReader($offset, $limit);
                break;

            case MediaDefinition::getEntityName():
                $reader = new Shopware55ApiAssetReader($offset, $limit);
                break;

            case CustomerDefinition::getEntityName():
                $reader = new Shopware55ApiCustomerReader($offset, $limit);
                break;

            case OrderDefinition::getEntityName():
                $reader = new Shopware55ApiOrderReader($offset, $limit);
                break;

//            case 'translation': TODO revert, when the core could handle translations correctly
//                $reader = new Shopware55ApiTranslationReader($offset, $limit);
//                break;

            case 'environment':
                $reader = new Shopware55ApiEnvironmentReader($apiClientOptions);
                break;

            default:
                throw new Shopware55ApiReaderNotFoundException($entityName);
        }

        return $reader->read($apiClient);
    }
}
