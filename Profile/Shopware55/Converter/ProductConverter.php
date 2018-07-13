<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductStruct;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class ProductConverter implements ConverterInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function convert(array $data): ConvertStruct
    {
        $converted = new ProductStruct();

        $converted->setName($data['name']);
        unset($data['name']);

        return new ConvertStruct($converted, $data);
    }
}
