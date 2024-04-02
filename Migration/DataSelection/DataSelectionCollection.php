<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Collection;

/**
 * @method void                     add(DataSelectionStruct $entity)
 * @method void                     set(string $key, DataSelectionStruct $entity)
 * @method DataSelectionStruct[]    getIterator()
 * @method DataSelectionStruct[]    getElements()
 * @method DataSelectionStruct|null get(string $key)
 * @method DataSelectionStruct|null first()
 * @method DataSelectionStruct|null last()
 */
#[Package('services-settings')]
class DataSelectionCollection extends Collection
{
    public function sortByPosition(): void
    {
        $this->sort(
            function (DataSelectionStruct $first, DataSelectionStruct $second) {
                if ($first->getPosition() == $second->getPosition()) {
                    return 0;
                }
                return ($first->getPosition() < $second->getPosition()) ? -1 : 1;
            }
        );
    }

    protected function getExpectedClass(): string
    {
        return DataSelectionStruct::class;
    }
}
