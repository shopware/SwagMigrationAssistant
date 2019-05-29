<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Reader;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\TableReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Shopware55ApiTableReader implements TableReaderInterface
{
    public function read(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        $client = ConnectionFactory::createApiClient($migrationContext);

        /** @var GuzzleResponse $result */
        $result = $client->get(
            'SwagMigrationDynamic',
            [
                'query' => [
                    'table' => $tableName,
                    'filter' => $filter,
                ],
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api ' . $tableName);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        return $arrayResult['data'];
    }
}
