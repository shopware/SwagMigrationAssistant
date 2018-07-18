<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

use SwagMigrationNext\Migration\MigrationContext;

interface GatewayFactoryRegistryInterface
{
    /**
     * Selects the correct GatewayFactory to create a gateway by the given migration context
     */
    public function createGateway(MigrationContext $context): GatewayInterface;
}
