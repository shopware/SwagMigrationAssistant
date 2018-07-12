<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Profile\ProfileRegistryInterface;

class MigrationService implements MigrationServiceInterface
{
    /**
     * @var ProfileRegistryInterface
     */
    private $profileRegistry;

    /**
     * @var GatewayFactoryRegistryInterface
     */
    private $gatewayService;

    public function __construct(
        ProfileRegistryInterface $profileRegistry,
        GatewayFactoryRegistryInterface $gatewayService
    ) {
        $this->profileRegistry = $profileRegistry;
        $this->gatewayService = $gatewayService;
    }

    public function fetchData(MigrationContext $migrationContext, Context $context): void
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getProfileName());

        $gateway = $this->gatewayService->createGateway($migrationContext);

        $profile->collectData($gateway, $migrationContext, $context);
    }
}
