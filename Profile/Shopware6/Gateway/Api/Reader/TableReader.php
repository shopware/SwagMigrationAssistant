<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader;

use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TableReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TableReader implements TableReaderInterface
{
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function read(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        $client = $this->connectionFactory->createApiClient($migrationContext);

        if ($client === null) {
            return [];
        }

        $result = $client->getRequest(
            'get-table',
            [
                'query' => [
                    'identifier' => $tableName,
                ],
            ]
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 6 Api table', 466);
        }

        return \json_decode($result->getBody()->getContents(), true);
    }
}
