<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Data;

use Shopware\Core\Framework\ORM\EntityCollection;

class SwagMigrationDataCollection extends EntityCollection
{
    /**
     * @var SwagMigrationDataStruct[]
     */
    protected $elements = [];

    public function first(): SwagMigrationDataStruct
    {
        return parent::first();
    }
}
