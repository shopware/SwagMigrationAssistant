<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface GatewayRegistryInterface
{
    /**
     * Selects the correct gateway by the given migration context
     */
    public function getGateway(MigrationContextInterface $migrationContext): GatewayInterface;
}
