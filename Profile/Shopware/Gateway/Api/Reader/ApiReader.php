<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiReader implements ReaderInterface
{
    /**
     * SwagMigrationConnector API endpoints for API gateway.
     *
     * @var array
     */
    protected $apiEndpoints = [
        DefaultEntities::CATEGORY_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::CATEGORY => 'SwagMigrationCategories',
        DefaultEntities::CURRENCY => 'SwagMigrationCurrencies',
        DefaultEntities::CUSTOMER_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::CUSTOMER => 'SwagMigrationCustomers',
        DefaultEntities::CUSTOMER_GROUP_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::CUSTOMER_GROUP => 'SwagMigrationCustomerGroups',
        DefaultEntities::LANGUAGE => 'SwagMigrationLanguages',
        DefaultEntities::PRODUCT_MANUFACTURER_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::MEDIA => 'SwagMigrationAssets',
        DefaultEntities::MEDIA_FOLDER => 'SwagMigrationMediaAlbums',
        DefaultEntities::NEWSLETTER_RECIPIENT => 'SwagMigrationNewsletterRecipients',
        DefaultEntities::NUMBER_RANGE => 'SwagMigrationNumberRanges',
        DefaultEntities::ORDER_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::ORDER => 'SwagMigrationOrders',
        DefaultEntities::ORDER_DOCUMENT_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::ORDER_DOCUMENT => 'SwagMigrationOrderDocuments',
        DefaultEntities::PRODUCT_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::PRODUCT => 'SwagMigrationProducts',
        DefaultEntities::PRODUCT_PRICE_CUSTOM_FIELD => 'SwagMigrationAttributes',
        DefaultEntities::PRODUCT_REVIEW => 'SwagMigrationVotes',
        DefaultEntities::PROPERTY_GROUP_OPTION => 'SwagMigrationConfiguratorOptions',
        DefaultEntities::SALES_CHANNEL => 'SwagMigrationShops',
        DefaultEntities::SEO_URL => 'SwagMigrationSeoUrls',
        DefaultEntities::SHIPPING_METHOD => 'SwagMigrationDispatches',
        DefaultEntities::TRANSLATION => 'SwagMigrationTranslations',
    ];

    /**
     * Holds extra query parameters for different entities.
     *
     * @var array
     */
    protected $extraParameters = [
        DefaultEntities::CATEGORY_CUSTOM_FIELD => [
            'attribute_table' => 's_categories_attributes',
        ],
        DefaultEntities::CUSTOMER_CUSTOM_FIELD => [
            'attribute_table' => 's_user_attributes',
        ],
        DefaultEntities::CUSTOMER_GROUP_CUSTOM_FIELD => [
            'attribute_table' => 's_core_customergroups_attributes',
        ],
        DefaultEntities::PRODUCT_MANUFACTURER_CUSTOM_FIELD => [
            'attribute_table' => 's_articles_supplier_attributes',
        ],
        DefaultEntities::ORDER_CUSTOM_FIELD => [
            'attribute_table' => 's_order_attributes',
        ],
        DefaultEntities::ORDER_DOCUMENT_CUSTOM_FIELD => [
            'attribute_table' => 's_order_documents_attributes',
        ],
        DefaultEntities::PRODUCT_CUSTOM_FIELD => [
            'attribute_table' => 's_articles_attributes',
        ],
        DefaultEntities::PRODUCT_PRICE_CUSTOM_FIELD => [
            'attribute_table' => 's_articles_prices_attributes',
        ],
    ];

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
        /** @var DataSet $dataSet */
        $dataSet = $migrationContext->getDataSet();

        if (!isset($this->apiEndpoints[$dataSet::getEntity()])) {
            throw new GatewayReadException('No endpoint for entity ' . $dataSet::getEntity() . ' available.');
        }
        $apiEndpoint = $this->apiEndpoints[$dataSet::getEntity()];

        $queryParams = [
            'offset' => $migrationContext->getOffset(),
            'limit' => $migrationContext->getLimit(),
        ];

        if (isset($this->extraParameters[$dataSet::getEntity()])) {
            $queryParams = array_merge($queryParams, $this->extraParameters[$dataSet::getEntity()]);
        }
        $client = $this->connectionFactory->createApiClient($migrationContext);

        /** @var GuzzleResponse $result */
        $result = $client->get(
            $apiEndpoint,
            [
                'query' => $queryParams,
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware Api ' . $dataSet::getEntity());
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        return null;
    }
}
