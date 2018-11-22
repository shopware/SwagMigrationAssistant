<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationNext\Exception\GatewayReadException;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\ReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiReader implements ReaderInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var MigrationContext
     */
    protected $migrationContext;

    public function __construct(Client $client, MigrationContext $migrationContext)
    {
        $this->client = $client;
        $this->migrationContext = $migrationContext;
    }

    /**
     * @throws GatewayReadException
     */
    public function read(): array
    {
        /** @var GuzzleResponse $result */
        $result = $this->client->get(
            'SwagMigration' . $this->migrationContext->getEntity(),
            ['query' => [
                'offset' => $this->migrationContext->getOffset(),
                'limit' => $this->migrationContext->getLimit(),
                ],
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api ' . $this->migrationContext->getEntity());
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
