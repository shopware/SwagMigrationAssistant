<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Data;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationDataCollection extends EntityCollection
{
    /**
     * @var SwagMigrationDataEntity[]
     */
    protected $elements = [];

    public function first(): SwagMigrationDataEntity
    {
        return parent::first();
    }
}
