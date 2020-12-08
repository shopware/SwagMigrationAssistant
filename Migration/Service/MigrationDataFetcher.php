<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class MigrationDataFetcher implements MigrationDataFetcherInterface
{
    /**
     * @var GatewayRegistryInterface
     */
    private $gatewayRegistry;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    public function __construct(
        GatewayRegistryInterface $gatewayRegistry,
        LoggingServiceInterface $loggingService
    ) {
        $this->gatewayRegistry = $gatewayRegistry;
        $this->loggingService = $loggingService;
    }

    public function fetchData(MigrationContextInterface $migrationContext, Context $context): array
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return [];
        }

        try {
            $gateway = $this->gatewayRegistry->getGateway($migrationContext);

            return $gateway->read($migrationContext);
        } catch (\Throwable $exception) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $migrationContext->getRunUuid(),
                $dataSet::getEntity(),
                $exception
            ));
            $this->loggingService->saveLogging($context);
        }

        return [];
    }

    public function getEnvironmentInformation(MigrationContextInterface $migrationContext, Context $context): EnvironmentInformation
    {
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);

        return $gateway->readEnvironmentInformation($migrationContext, $context);
    }

    public function fetchTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        $gateway = $this->gatewayRegistry->getGateway($migrationContext);

        return $gateway->readTotals($migrationContext, $context);
    }
}
