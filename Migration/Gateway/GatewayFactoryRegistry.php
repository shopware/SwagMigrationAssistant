<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Exception\GatewayNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class GatewayFactoryRegistry implements GatewayFactoryRegistryInterface
{
    /**
     * @var GatewayFactoryInterface[]
     */
    private $gatewayFactories;

    public function __construct(iterable $gatewayFactories)
    {
        $this->gatewayFactories = $gatewayFactories;
    }

    /**
     * @throws GatewayNotFoundException
     */
    public function createGateway(MigrationContextInterface $migrationContext): GatewayInterface
    {
        $gatewayIdentifier = $migrationContext->getProfileName() . $migrationContext->getGatewayName();

        foreach ($this->gatewayFactories as $gatewayFactory) {
            if ($gatewayFactory->supports($gatewayIdentifier)) {
                return $gatewayFactory->create($migrationContext);
            }
        }

        throw new GatewayNotFoundException($gatewayIdentifier);
    }
}
