<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\System\Tax\TaxDefinition;

class EntityRelationMapping
{
    /**
     * @throws EntityRelationMappingNotFoundException
     */
    public static function getMapping(string $entityName): array
    {
        switch ($entityName) {
            case ProductDefinition::getEntityName():
                return [
                    TaxDefinition::getEntityName(),
                    ProductManufacturerDefinition::getEntityName(),
                    'main' => ProductDefinition::getEntityName(),
                ];
            case CustomerDefinition::getEntityName():
                return [
                    'main' => CustomerDefinition::getEntityName(),
                ];
        }

        throw new EntityRelationMappingNotFoundException($entityName);
    }
}
