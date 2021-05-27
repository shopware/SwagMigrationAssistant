<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TotalReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TotalReader implements TotalReaderInterface
{
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $client = $this->connectionFactory->createApiClient($migrationContext);

        if ($client === null) {
            return [];
        }

        $result = $client->getRequest(
            'get-total',
            []
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 6 Api total', 466);
        }

        $decoded = \json_decode($result->getBody()->getContents(), true);

        return $this->prepareTotals($decoded);
    }

    /**
     * @return TotalStruct[]
     */
    protected function prepareTotals(array $rawTotals): array
    {
        $totals = [];
        foreach ($rawTotals as $identifier => $total) {
            $totals[$identifier] = new TotalStruct($identifier, $total);
        }

        return $totals;
    }
}
