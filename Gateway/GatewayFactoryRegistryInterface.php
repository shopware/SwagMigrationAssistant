<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

use SwagMigrationNext\Migration\MigrationContext;

interface GatewayFactoryRegistryInterface
{
    /**
     * Selects the correct GatewayFactory to create a gateway by the given migration context
     *
     * @param MigrationContext $context
     *
     * @return GatewayInterface
     */
    public function createGateway(MigrationContext $context): GatewayInterface;
}
