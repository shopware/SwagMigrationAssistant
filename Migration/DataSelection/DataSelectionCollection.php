<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use Shopware\Core\Framework\Struct\Collection;

class DataSelectionCollection extends Collection
{
    public function first(): DataSelectionStruct
    {
        return parent::first();
    }

    public function sortByPosition(): void
    {
        $this->sort(
            function (DataSelectionStruct $first, DataSelectionStruct $second) {
                return $first->getPosition() > $second->getPosition();
            }
        );
    }

    protected function getExpectedClass(): string
    {
        return DataSelectionStruct::class;
    }
}
