<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                            add(SwagMigrationMappingEntity $entity)
 * @method void                            set(string $key, SwagMigrationMappingEntity $entity)
 * @method SwagMigrationMappingEntity[]    getIterator()
 * @method SwagMigrationMappingEntity[]    getElements()
 * @method SwagMigrationMappingEntity|null get(string $key)
 * @method SwagMigrationMappingEntity|null first()
 * @method SwagMigrationMappingEntity|null last()
 */
class SwagMigrationMappingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationMappingEntity::class;
    }
}
