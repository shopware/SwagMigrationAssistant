<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Connection;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                               add(SwagMigrationConnectionEntity $entity)
 * @method void                               set(string $key, SwagMigrationConnectionEntity $entity)
 * @method SwagMigrationConnectionEntity[]    getIterator()
 * @method SwagMigrationConnectionEntity[]    getElements()
 * @method SwagMigrationConnectionEntity|null get(string $key)
 * @method SwagMigrationConnectionEntity|null first()
 * @method SwagMigrationConnectionEntity|null last()
 */
class SwagMigrationConnectionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationConnectionEntity::class;
    }
}
