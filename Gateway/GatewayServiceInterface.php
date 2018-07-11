<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway;

use SwagMigrationNext\Migration\MigrationContext;

interface GatewayServiceInterface
{
    public function getGateway(MigrationContext $context);
}
