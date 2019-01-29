<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Connection;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationConnectionCollection extends EntityCollection
{
    public function first(): SwagMigrationConnectionEntity
    {
        return parent::first();
    }

    protected function getExpectedClass(): string
    {
        return SwagMigrationConnectionEntity::class;
    }
}
