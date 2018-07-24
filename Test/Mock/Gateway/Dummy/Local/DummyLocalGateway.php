<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Local;

use SwagMigrationNext\Gateway\GatewayInterface;

class DummyLocalGateway implements GatewayInterface
{
    public const GATEWAY_TYPE = 'local';

    public function read(string $entityName): array
    {
        return require __DIR__ . '/../../../../_fixtures/product_data.php';
    }
}
