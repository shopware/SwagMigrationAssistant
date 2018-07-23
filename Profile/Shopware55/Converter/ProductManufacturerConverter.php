<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class ProductManufacturerConverter implements ConverterInterface
{
    public function supports(): string
    {
        return ProductManufacturerDefinition::getEntityName();
    }

    public function convert(array $data, array $additionalRelationData = []): ConvertStruct
    {
        $converted = [];
        $oldId = $data['id'];
        $uuid = Uuid::uuid4()->getHex();

        $converted['id'] = $uuid;

        $converted['name'] = $data['name'];
        unset($data['name']);

        $converted['link'] = $data['link'];
        unset($data['link']);

        $converted['description'] = $data['description'];
        unset($data['description']);

        $converted['metaTitle'] = $data['meta_title'];
        unset($data['meta_title']);

        $converted['metaDescription'] = $data['meta_description'];
        unset($data['meta_description']);

        $converted['metaKeywords'] = $data['meta_keywords'];
        unset($data['meta_keywords']);

        return new ConvertStruct($converted, $data, $oldId, $uuid);
    }
}
