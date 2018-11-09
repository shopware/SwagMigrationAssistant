<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationMediaFileCollection extends EntityCollection
{
    /**
     * @var SwagMigrationMediaFileStruct[]
     */
    protected $elements = [];

    public function first(): SwagMigrationMediaFileStruct
    {
        return parent::first();
    }
}
