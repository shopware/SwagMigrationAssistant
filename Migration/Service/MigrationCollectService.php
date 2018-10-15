<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Profile\ProfileRegistryInterface;

class MigrationCollectService implements MigrationCollectServiceInterface
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

    public function fetchData(MigrationContext $migrationContext, Context $context): int
    {
        try {
            $profile = $this->profileRegistry->getProfile($migrationContext->getProfile());
            $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);
            $returnCount = $profile->collectData($gateway, $migrationContext, $context);
        } catch (\Exception $exception) {
            $this->loggingService->addError($migrationContext->getRunUuid(), (string) $exception->getCode(), $exception->getMessage(), ['entity' => $migrationContext->getEntity()]);
            $this->loggingService->saveLogging($context);
            $returnCount = 0;
        }

        return $returnCount;
    }
}
