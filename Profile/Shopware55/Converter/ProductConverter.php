<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Ramsey\Uuid\Uuid;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\System\Tax\TaxDefinition;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class ProductConverter implements ConverterInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function convert(array $data, array $additionalRelationData = []): ConvertStruct
    {
        $oldId = $data['variantID'];
        $uuid = Uuid::uuid4()->getHex();
        $converted['id'] = $uuid;

        $converted['name'] = $data['name'];
        unset($data['name']);

        if (array_key_exists($data['supplierID'], $additionalRelationData[ProductManufacturerDefinition::getEntityName()])) {
            $converted['manufacturer']['id'] = $additionalRelationData[ProductManufacturerDefinition::getEntityName()][$data['supplierID']];
        }

        if (array_key_exists($data['taxID'], $additionalRelationData[TaxDefinition::getEntityName()])) {
            $converted['tax']['id'] = $additionalRelationData[TaxDefinition::getEntityName()][$data['taxID']];
        }

        if (!empty($data['prices'])) {
            foreach ($data['prices'] as $price) {
                $converted['price'] = [
                    'gross' => (float) $price['price'],
                    'net' => (float) $price['price'],
                ];
            }
        }

        return new ConvertStruct($converted, $data, $oldId, $uuid);
    }
}
