<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Profile\ReaderInterface;
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

        $this->routeMapping = [
            MediaDefinition::getEntityName() => 'Assets',
            CategoryDefinition::getEntityName() => 'Categories',
            CustomerDefinition::getEntityName() => 'Customers',
            OrderDefinition::getEntityName() => 'Orders',
            ProductDefinition::getEntityName() => 'Products',
        ];
    }

    /**
     * @throws GatewayReadException
     */
    public function read(): array
    {
        $entity = $this->migrationContext->getEntity();

        if (!isset($this->routeMapping[$entity])) {
            throw new GatewayReadException('No endpoint for entity ' . $entity . ' available.');
        }
        $route = $this->routeMapping[$entity];

        /** @var GuzzleResponse $result */
        $result = $this->client->get(
            'SwagMigration' . $route,
            [
                'query' => [
                    'offset' => $this->migrationContext->getOffset(),
                    'limit' => $this->migrationContext->getLimit(),
                ],
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api ' . $entity);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
