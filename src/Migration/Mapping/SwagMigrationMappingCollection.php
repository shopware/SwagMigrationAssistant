<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @method void add(SwagMigrationMappingEntity $entity)
 * @method void set(string $key, SwagMigrationMappingEntity $entity)
 * @method SwagMigrationMappingEntity[] getIterator()
 * @method SwagMigrationMappingEntity[] getElements()
 * @method SwagMigrationMappingEntity|null get(string $key)
 * @method SwagMigrationMappingEntity|null first()
 * @method SwagMigrationMappingEntity|null last()
 */
#[Package('services-settings')]
class SwagMigrationMappingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationMappingEntity::class;
    }
}
