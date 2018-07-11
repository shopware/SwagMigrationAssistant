<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

use SwagMigrationNext\Migration\MigrationContext;

interface GatewayFactoryInterface
{
    public function getName(): string;

    public function createGateway(MigrationContext $context);
}
