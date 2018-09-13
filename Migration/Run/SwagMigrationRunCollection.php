<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\ORM\EntityCollection;

class SwagMigrationRunCollection extends EntityCollection
{
    /**
     * @var SwagMigrationRunStruct[]
     */
    protected $elements = [];

    public function first(): SwagMigrationRunStruct
    {
        return parent::first();
    }
}
