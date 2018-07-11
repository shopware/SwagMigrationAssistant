<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

use IteratorAggregate;
use SwagMigrationNext\Migration\MigrationContext;

class GatewayService implements GatewayServiceInterface
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
    public function getGateway(MigrationContext $context)
    {
        foreach ($this->gatewayFactories as $gatewayFactory) {
            if ($gatewayFactory->getName() === $context->getGatewayIdentifier()) {
                return $gatewayFactory->createGateway($context);
            }
        }

        throw new GatewayNotFoundException(sprintf('Gateway "%s" not found', $context->getGatewayIdentifier()));
    }
}
