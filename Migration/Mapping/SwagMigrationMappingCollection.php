<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationMappingCollection extends EntityCollection
{
    /**
     * @var SwagMigrationMappingStruct[]
     */
    protected $elements = [];

    public function first(): SwagMigrationMappingStruct
    {
        return parent::first();
    }
}
