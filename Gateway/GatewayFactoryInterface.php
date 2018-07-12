<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

use SwagMigrationNext\Migration\MigrationContext;

interface GatewayFactoryInterface
{
    /**
     * Identifier for a gateway factory
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Creates an instance of gateway. The context contains possible credentials to set up the connection
     *
     * @param MigrationContext $context
     *
     * @return GatewayInterface
     */
    public function create(MigrationContext $context): GatewayInterface;
}
