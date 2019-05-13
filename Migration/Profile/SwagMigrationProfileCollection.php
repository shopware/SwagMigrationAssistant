<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Profile;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                            add(SwagMigrationProfileEntity $entity)
 * @method void                            set(string $key, SwagMigrationProfileEntity $entity)
 * @method SwagMigrationProfileEntity[]    getIterator()
 * @method SwagMigrationProfileEntity[]    getElements()
 * @method SwagMigrationProfileEntity|null get(string $key)
 * @method SwagMigrationProfileEntity|null first()
 * @method SwagMigrationProfileEntity|null last()
 */
class SwagMigrationProfileCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationProfileEntity::class;
    }
}
