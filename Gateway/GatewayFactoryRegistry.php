<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

use IteratorAggregate;
use SwagMigrationNext\Migration\MigrationContext;

class GatewayFactoryRegistry implements GatewayFactoryRegistryInterface
{
    /**
     * @var GatewayFactoryInterface[]
     */
    private $gatewayFactories;

    /**
     * @param IteratorAggregate $gatewayFactories
     */
    public function __construct(IteratorAggregate $gatewayFactories)
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
