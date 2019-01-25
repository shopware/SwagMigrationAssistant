<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use Shopware\Core\Framework\Struct\Collection;

class DataSelectionCollection extends Collection
{
    public function first(): DataSelectionStruct
    {
        return parent::first();
    }

    protected function getExpectedClass(): string
    {
        return DataSelectionStruct::class;
    }
}
