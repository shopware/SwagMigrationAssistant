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
 * @template-extends Collection<DataSelectionStruct>
 */
#[Package('services-settings')]
class DataSelectionCollection extends Collection
{
    public function sortByPosition(): void
    {
        $this->sort(
            function (DataSelectionStruct $first, DataSelectionStruct $second) {
                return $first->getPosition() <=> $second->getPosition();
            }
        );
    }

    protected function getExpectedClass(): string
    {
        return DataSelectionStruct::class;
    }
}
