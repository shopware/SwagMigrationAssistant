<?php declare(strict_types=1);


namespace SwagMigrationNext\Migration\Logging;


use Shopware\Core\Framework\ORM\EntityCollection;

class SwagMigrationLoggingCollection extends EntityCollection
{
    /**
     * @var SwagMigrationLoggingStruct[]
     */
    protected $elements = [];

    public function first(): SwagMigrationLoggingStruct
    {
        return parent::first();
    }
}