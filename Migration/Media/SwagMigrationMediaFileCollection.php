<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                              add(SwagMigrationMediaFileEntity $entity)
 * @method void                              set(string $key, SwagMigrationMediaFileEntity $entity)
 * @method SwagMigrationMediaFileEntity[]    getIterator()
 * @method SwagMigrationMediaFileEntity[]    getElements()
 * @method SwagMigrationMediaFileEntity|null get(string $key)
 * @method SwagMigrationMediaFileEntity|null first()
 * @method SwagMigrationMediaFileEntity|null last()
 */
class SwagMigrationMediaFileCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationMediaFileEntity::class;
    }
}
