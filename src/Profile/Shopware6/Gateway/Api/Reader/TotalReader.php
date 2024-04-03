<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\TotalReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Package('services-settings')]
class TotalReader implements TotalReaderInterface
{
    public function __construct(private readonly ConnectionFactoryInterface $connectionFactory)
    {
    }

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $client = $this->connectionFactory->createApiClient($migrationContext);

        if ($client === null) {
            return [];
        }

        $result = $client->get(
            'get-total',
            []
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw MigrationException::gatewayRead('Shopware 6 Api total');
        }

        $decoded = \json_decode($result->getBody()->getContents(), true);

        return $this->prepareTotals($decoded);
    }

    /**
     * @param array<string, int> $rawTotals
     *
     * @return array<string, TotalStruct>
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
