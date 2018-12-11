<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Setting;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class GeneralSettingCollection extends EntityCollection
{
    /**
     * @var GeneralSettingEntity[]
     */
    protected $elements = [];

    public function first(): GeneralSettingEntity
    {
        return parent::first();
    }
}
