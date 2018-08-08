<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationNext\Exception\GatewayReadException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

abstract class Shopware55ApiAbstractReader
{
    /**
     * @var string
     */
    protected $entity;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var int
     */
    protected $limit;

    public function __construct(string $entity, int $offset, int $limit)
    {
        $this->entity = $entity;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * @throws GatewayReadException
     */
    public function read(Client $apiClient): array
    {
        /** @var GuzzleResponse $result */
        $result = $apiClient->get(
            'SwagMigration' . $this->entity,
            ['query' => ['offset' => $this->offset, 'limit' => $this->limit]]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api ' . $this->entity);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
