<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Shopware6ApiGateway;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

abstract class ApiReader implements ReaderInterface
{
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
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface
            && $migrationContext->getGateway()->getName() === Shopware6ApiGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === $this->getIdentifier();
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return false;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $queryParams = [
            'identifier' => $this->getIdentifier(),
            'offset' => $migrationContext->getOffset(),
            'limit' => $migrationContext->getLimit(),
        ];

        $queryParams = \array_merge($queryParams, $this->getExtraParameters());
        $client = $this->connectionFactory->createApiClient($migrationContext);

        if ($client === null) {
            return [];
        }

        /** @var GuzzleResponse $result */
        $result = $client->getRequest(
            'get-data',
            [
                'query' => $queryParams,
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware Api ' . $this->getDataSetEntity($migrationContext));
        }

        return \json_decode($result->getBody()->getContents(), true);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        return null;
    }

    abstract protected function getIdentifier(): string;

    /**
     * @return string[]
     */
    protected function getExtraParameters(): array
    {
        return [];
    }

    protected function getDataSetEntity(MigrationContextInterface $migrationContext): ?string
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return null;
        }

        return $dataSet::getEntity();
    }
}
