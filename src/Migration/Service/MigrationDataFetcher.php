<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistryInterface;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class MigrationDataFetcher implements MigrationDataFetcherInterface
{
    public function __construct(
        private readonly GatewayRegistryInterface $gatewayRegistry,
        private readonly LoggingServiceInterface $loggingService
    ) {
    }

    public function fetchData(MigrationContextInterface $migrationContext, Context $context): array
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return [];
        }

        try {
            return $this->gatewayRegistry->getGateway($migrationContext)->read($migrationContext);
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
        return $this->gatewayRegistry->getGateway($migrationContext)->readEnvironmentInformation($migrationContext, $context);
    }

    public function fetchTotals(MigrationContextInterface $migrationContext, Context $context): array
    {
        return $this->gatewayRegistry->getGateway($migrationContext)->readTotals($migrationContext, $context);
    }
}
