<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Data;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationDataCollection extends EntityCollection
{
    public function first(): SwagMigrationDataEntity
    {
        return parent::first();
    }

    protected function getExpectedClass(): string
    {
        return SwagMigrationDataEntity::class;
    }
}
