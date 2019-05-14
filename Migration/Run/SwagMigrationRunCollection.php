<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                        add(SwagMigrationRunEntity $entity)
 * @method void                        set(string $key, SwagMigrationRunEntity $entity)
 * @method SwagMigrationRunEntity[]    getIterator()
 * @method SwagMigrationRunEntity[]    getElements()
 * @method SwagMigrationRunEntity|null get(string $key)
 * @method SwagMigrationRunEntity|null first()
 * @method SwagMigrationRunEntity|null last()
 */
class SwagMigrationRunCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationRunEntity::class;
    }
}
