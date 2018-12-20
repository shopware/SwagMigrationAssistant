<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationMappingCollection extends EntityCollection
{
    public function first(): SwagMigrationMappingEntity
    {
        return parent::first();
    }

    protected function getExpectedClass(): string
    {
        return SwagMigrationMappingEntity::class;
    }
}
