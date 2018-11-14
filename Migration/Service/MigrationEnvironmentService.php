<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use SwagMigrationNext\Migration\Gateway\GatewayFactoryRegistryInterface;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\ProfileRegistryInterface;

class MigrationEnvironmentService implements MigrationEnvironmentServiceInterface
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

    public function getEntityTotal(MigrationContext $migrationContext): int
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getProfileName());
        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);

        return $profile->readEntityTotal($gateway, $migrationContext->getEntity());
    }

    public function getEnvironmentInformation(MigrationContext $migrationContext): EnvironmentInformation
    {
        $profile = $this->profileRegistry->getProfile($migrationContext->getProfileName());
        $gateway = $this->gatewayFactoryRegistry->createGateway($migrationContext);

        return $profile->readEnvironmentInformation($gateway);
    }
}
