<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Gateway;

use SwagMigrationNext\Exception\GatewayNotFoundException;
use SwagMigrationNext\Migration\MigrationContext;

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
    public function createGateway(MigrationContext $context): GatewayInterface
    {
        foreach ($this->gatewayFactories as $gatewayFactory) {
            if ($gatewayFactory->getName() === $context->getGatewayIdentifier()) {
                return $gatewayFactory->create($context);
            }
        }

        throw new GatewayNotFoundException($context->getGatewayIdentifier());
    }
}
