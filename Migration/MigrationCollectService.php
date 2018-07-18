<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Gateway\GatewayFactoryRegistryInterface;
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

    public function __construct(
        ProfileRegistryInterface $profileRegistry,
        GatewayFactoryRegistryInterface $gatewayFactoryRegistry
    ) {
        $this->profileRegistry = $profileRegistry;
        $this->gatewayFactoryRegistry = $gatewayFactoryRegistry;
    }

    public function fetchData(MigrationContext $migrationContext, Context $context): void
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getProfileName());

        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);

        $profile->collectData($gateway, $migrationContext, $context);
    }
}
