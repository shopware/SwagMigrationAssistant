<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductPriceRule\ProductPriceRuleDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\System\Tax\TaxDefinition;

class EntityRelationMapping
{
    public const MAIN = 1;
    public const MANYTOONE = 2;
    public const ONETOMANY = 3;

    /**
     * @throws EntityRelationMappingNotFoundException
     */
    public static function getMapping(string $entityName): array
    {
        switch ($entityName) {
            case ProductDefinition::getEntityName():
                return [
                    [ 'entity' => TaxDefinition::getEntityName(), 'relation' => self::MANYTOONE ],
                    [ 'entity' => ProductManufacturerDefinition::getEntityName(), 'relation' => self::MANYTOONE ],
                    [ 'entity' => ProductDefinition::getEntityName(), 'relation' => self::MAIN ],
                    [ 'entity' => ProductPriceRuleDefinition::getEntityName(), 'relation' => self::ONETOMANY ],
                ];
            case CustomerDefinition::getEntityName():
                return [
                    [ 'entity' => CustomerDefinition::getEntityName(), 'relation' => self::MAIN ],
                ];
        }

        throw new EntityRelationMappingNotFoundException($entityName);
    }
}
