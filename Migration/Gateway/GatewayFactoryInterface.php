<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Gateway;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface GatewayFactoryInterface
{
    /**
     * Identifier for a gateway factory
     */
    public function supports(string $gatewayIdentifier): bool;

    /**
     * Creates an instance of gateway. The context contains possible credentials to set up the connection
     */
    public function create(MigrationContextInterface $context): GatewayInterface;
}
