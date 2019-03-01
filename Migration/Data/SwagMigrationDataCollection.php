<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Data;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(SwagMigrationDataEntity $entity)
 * @method void                         set(string $key, SwagMigrationDataEntity $entity)
 * @method SwagMigrationDataEntity[]    getIterator()
 * @method SwagMigrationDataEntity[]    getElements()
 * @method SwagMigrationDataEntity|null get(string $key)
 * @method SwagMigrationDataEntity|null first()
 * @method SwagMigrationDataEntity|null last()
 */
class SwagMigrationDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationDataEntity::class;
    }
}
