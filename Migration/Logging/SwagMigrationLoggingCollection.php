<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                            add(SwagMigrationLoggingEntity $entity)
 * @method void                            set(string $key, SwagMigrationLoggingEntity $entity)
 * @method SwagMigrationLoggingEntity[]    getIterator()
 * @method SwagMigrationLoggingEntity[]    getElements()
 * @method SwagMigrationLoggingEntity|null get(string $key)
 * @method SwagMigrationLoggingEntity|null first()
 * @method SwagMigrationLoggingEntity|null last()
 */
class SwagMigrationLoggingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationLoggingEntity::class;
    }
}
