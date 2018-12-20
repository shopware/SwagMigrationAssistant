<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationLoggingCollection extends EntityCollection
{
    public function first(): SwagMigrationLoggingEntity
    {
        return parent::first();
    }

    protected function getExpectedClass(): string
    {
        return SwagMigrationLoggingEntity::class;
    }
}
