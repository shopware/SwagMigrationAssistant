<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Gateway;

use SwagMigrationNext\Migration\MigrationContextInterface;

interface GatewayFactoryRegistryInterface
{
    /**
     * Selects the correct GatewayFactory to create a gateway by the given migration context
     */
    public function createGateway(MigrationContextInterface $migrationContext): GatewayInterface;
}
