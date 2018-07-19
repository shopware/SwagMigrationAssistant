<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Local;

use SwagMigrationNext\Gateway\GatewayInterface;

class DummyLocalGateway implements GatewayInterface
{
    public const GATEWAY_TYPE = 'local';

    public function read(string $entityName): array
    {
        $data = require __DIR__ . '/../../../../_fixtures/product_data.php';

        foreach ($data as &$item) {
            if (!empty($item['supplierID'])) {
                $item['supplier.name'] = 'TestSupplierName';
            }

            $item['tax.rate'] = 19;
            $item['tax.name'] = 'TestTaxName';

            $item['prices'] = [
                100,
                200,
                300,
            ];
        }

        return $data;
    }
}
