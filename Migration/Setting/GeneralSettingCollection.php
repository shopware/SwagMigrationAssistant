<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                      add(GeneralSettingEntity $entity)
 * @method void                      set(string $key, GeneralSettingEntity $entity)
 * @method GeneralSettingEntity[]    getIterator()
 * @method GeneralSettingEntity[]    getElements()
 * @method GeneralSettingEntity|null get(string $key)
 * @method GeneralSettingEntity|null first()
 * @method GeneralSettingEntity|null last()
 */
class GeneralSettingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return GeneralSettingEntity::class;
    }
}
