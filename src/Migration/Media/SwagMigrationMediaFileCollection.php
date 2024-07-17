<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @method void add(SwagMigrationMediaFileEntity $entity)
 * @method void set(string $key, SwagMigrationMediaFileEntity $entity)
 * @method SwagMigrationMediaFileEntity[] getIterator()
 * @method SwagMigrationMediaFileEntity[] getElements()
 * @method SwagMigrationMediaFileEntity|null get(string $key)
 * @method SwagMigrationMediaFileEntity|null first()
 * @method SwagMigrationMediaFileEntity|null last()
 */
#[Package('services-settings')]
class SwagMigrationMediaFileCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationMediaFileEntity::class;
    }
}
