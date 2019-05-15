<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Exception\GatewayNotFoundException;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class GatewayRegistry implements GatewayRegistryInterface
{
    /**
     * @var GatewayInterface[]
     */
    private $gateways;

    public function __construct(iterable $gateways)
    {
        $this->gateways = $gateways;
    }

    /**
     * @throws GatewayNotFoundException
     */
    public function getGateway(MigrationContextInterface $migrationContext): GatewayInterface
    {
        $gatewayIdentifier = $migrationContext->getProfileName() . $migrationContext->getGatewayName();

        foreach ($this->gateways as $gateway) {
            if ($gateway->supports($gatewayIdentifier)) {
                return $gateway;
            }
        }

        throw new GatewayNotFoundException($gatewayIdentifier);
    }
}
