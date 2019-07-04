<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway;

use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface ShopwareGatewayInterface extends GatewayInterface
{
    public function readTable(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array;
}
