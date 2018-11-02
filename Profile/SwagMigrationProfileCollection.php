<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationProfileCollection extends EntityCollection
{
    /**
     * @var SwagMigrationProfileStruct[]
     */
    protected $elements = [];

    public function first(): SwagMigrationProfileStruct
    {
        return parent::first();
    }
}
