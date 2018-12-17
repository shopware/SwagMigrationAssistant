<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Profile;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationProfileCollection extends EntityCollection
{
    /**
     * @var SwagMigrationProfileEntity[]
     */
    protected $elements = [];

    public function first(): SwagMigrationProfileEntity
    {
        return parent::first();
    }
}
