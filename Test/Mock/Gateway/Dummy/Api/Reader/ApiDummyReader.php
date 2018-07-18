<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Gateway\Dummy\Api\Reader;

use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiClient;
use SwagMigrationNext\Gateway\Shopware55\Api\Reader\Shopware55ApiReaderInterface;

class ApiDummyReader implements Shopware55ApiReaderInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function read(Shopware55ApiClient $apiClient): array
    {
        $data = require __DIR__ . '/../../../../../_fixtures/product_data.php';

        foreach ($data['data'] as &$item) {
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
