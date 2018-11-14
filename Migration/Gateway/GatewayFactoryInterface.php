<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Gateway;

use SwagMigrationNext\Migration\MigrationContext;

interface GatewayFactoryInterface
{
    /**
     * Identifier for a gateway factory
     */
    public function getName(): string;

    /**
     * Creates an instance of gateway. The context contains possible credentials to set up the connection
     */
    public function create(MigrationContext $context): GatewayInterface;
}
