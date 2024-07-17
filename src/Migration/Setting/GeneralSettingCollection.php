<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @method void add(GeneralSettingEntity $entity)
 * @method void set(string $key, GeneralSettingEntity $entity)
 * @method GeneralSettingEntity[] getIterator()
 * @method GeneralSettingEntity[] getElements()
 * @method GeneralSettingEntity|null get(string $key)
 * @method GeneralSettingEntity|null first()
 * @method GeneralSettingEntity|null last()
 */
#[Package('services-settings')]
class GeneralSettingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return GeneralSettingEntity::class;
    }
}
