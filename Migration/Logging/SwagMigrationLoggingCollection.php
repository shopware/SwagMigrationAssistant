<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Logging;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class SwagMigrationLoggingCollection extends EntityCollection
{
    /**
     * @var SwagMigrationLoggingEntity[]
     */
    protected $elements = [];

    public function first(): SwagMigrationLoggingEntity
    {
        return parent::first();
    }
}
