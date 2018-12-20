<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Run;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationRunCollection extends EntityCollection
{
    public function first(): SwagMigrationRunEntity
    {
        return parent::first();
    }

    protected function getExpectedClass(): string
    {
        return SwagMigrationRunEntity::class;
    }
}
