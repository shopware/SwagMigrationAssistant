<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationMediaFileCollection extends EntityCollection
{
    public function first(): SwagMigrationMediaFileEntity
    {
        return parent::first();
    }

    protected function getExpectedClass(): string
    {
        return SwagMigrationMediaFileEntity::class;
    }
}
