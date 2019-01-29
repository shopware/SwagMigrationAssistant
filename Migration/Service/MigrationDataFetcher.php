<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Profile\ProfileRegistryInterface;

class MigrationDataFetcher implements MigrationDataFetcherInterface
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayFactoryRegistryInterface
     */
    private $gatewayFactoryRegistry;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    public function __construct(
        ProfileRegistryInterface $profileRegistry,
        GatewayFactoryRegistryInterface $gatewayFactoryRegistry,
        LoggingServiceInterface $loggingService
    ) {
        $this->profileRegistry = $profileRegistry;
        $this->gatewayFactoryRegistry = $gatewayFactoryRegistry;
        $this->loggingService = $loggingService;
    }

    public function fetchData(MigrationContextInterface $migrationContext, Context $context): int
    {
        $returnCount = 0;
        try {
            $profile = $this->profileRegistry->getProfile($migrationContext->getConnection()->getProfile()->getName());
            $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);
            $data = $gateway->read();

            if (\count($data) === 0) {
                return $returnCount;
            }
            $returnCount = $profile->convert($data, $migrationContext, $context);
        } catch (\Exception $exception) {
            $this->loggingService->addError($migrationContext->getRunUuid(), (string) $exception->getCode(), '', $exception->getMessage(), ['entity' => $migrationContext->getEntity()]);
            $this->loggingService->saveLogging($context);
        }

        return $returnCount;
    }

    public function getEntityTotal(MigrationContextInterface $migrationContext): int
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getConnection()->getProfile()->getName());
        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);

        return $profile->readEntityTotal($gateway, $migrationContext->getEntity());
    }

    public function getEnvironmentInformation(MigrationContextInterface $migrationContext): EnvironmentInformation
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getConnection()->getProfile()->getName());
        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);

        return $profile->readEnvironmentInformation($gateway);
    }
}
