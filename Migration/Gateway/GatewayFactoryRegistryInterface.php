<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface GatewayFactoryRegistryInterface
{
    /**
     * Selects the correct GatewayFactory to create a gateway by the given migration context
     */
    public function createGateway(MigrationContextInterface $migrationContext): GatewayInterface;
}
