<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Logging\Log\CannotReadEntityCountLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableCountReaderInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Package('services-settings')]
class TableCountReader implements TableCountReaderInterface
{
    public function __construct(
        private readonly ConnectionFactoryInterface $connectionFactory,
        private readonly LoggingService $loggingService
    ) {
    }

    public function readTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $client = $this->connectionFactory->createApiClient($migrationContext);

        if ($client === null) {
            return [];
        }

        $result = $client->get(
            'SwagMigrationTotals'
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw MigrationException::gatewayRead('Shopware Api table count.');
        }

        $arrayResult = \json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            return [];
        }

        if (\count($arrayResult['data']['exceptions']) > 0) {
            $this->logExceptions($arrayResult['data']['exceptions'], $migrationContext, $context);
        }

        return $this->prepareTotals($arrayResult['data']['totals']);
    }

    /**
     * @return TotalStruct[]
     */
    private function prepareTotals(array $result): array
    {
        $totals = [];
        foreach ($result as $key => $tableResult) {
            $totals[$key] = new TotalStruct($key, $tableResult);
        }

        return $totals;
    }

    private function logExceptions(array $exceptionArray, MigrationContextInterface $migrationContext, Context $context): void
    {
        foreach ($exceptionArray as $exception) {
            $this->loggingService->addLogEntry(new CannotReadEntityCountLog(
                $migrationContext->getRunUuid(),
                $exception['entity'],
                $exception['table'],
                $exception['condition'],
                $exception['code'],
                $exception['message']
            ));
        }

        $this->loggingService->saveLogging($context);
    }
}
