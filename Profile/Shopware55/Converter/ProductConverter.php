<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Ramsey\Uuid\Uuid;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class ProductConverter implements ConverterInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function convert(array $data): ConvertStruct
    {
        $converted['id'] = Uuid::uuid4()->getHex();

        $converted['name'] = $data['name'];
        unset($data['name']);

        if (!empty($data['supplierID'])) {
            $converted['manufacturer']['id'] = Uuid::uuid4()->getHex();
            $converted['manufacturer']['name'] = $data['supplier.name'];
        }

        if (!empty($data['taxID'])) {
            $converted['tax']['id'] = Uuid::uuid4()->getHex();
            $converted['tax']['rate'] = $data['tax.rate'];
            $converted['tax']['name'] = $data['tax.name'];
        }

        if (!empty($data['prices'])) {
            foreach ($data['prices'] as $price) {
                $converted['price'] = [
                    'gross' => $price,
                    'net' => $price,
                ];
            }
        }

        return new ConvertStruct($converted, $data);
    }
}
