<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationRunCollection extends EntityCollection
{
    /**
     * @var SwagMigrationRunEntity[]
     */
    protected $elements = [];

    public function first(): SwagMigrationRunEntity
    {
        return parent::first();
    }
}
