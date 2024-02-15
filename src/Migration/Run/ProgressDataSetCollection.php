<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Run;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Collection;

/**
 * @internal
 *
 * @extends Collection<ProgressDataSet>
 */
#[Package('services-settings')]
class ProgressDataSetCollection extends Collection
{
    /**
     * @param list<array<string, string|int>> $data
     */
    public function fromArray(array $data): void
    {
        foreach ($data as $item) {
            if (
                !isset($item['entityName'], $item['total'])
                || !\is_string($item['entityName'])
                || !\is_int($item['total'])
            ) {
                continue;
            }

            $this->set($item['entityName'], new ProgressDataSet($item['entityName'], $item['total']));
        }
    }

    /**
     * @return array<string>
     */
    public function getEntityNames(): array
    {
        return $this->map(function (ProgressDataSet $progressDataSet) {
            return $progressDataSet->getEntityName();
        });
    }

    public function getTotalByEntityName(string $entityName): int
    {
        $progressDataSet = $this->get($entityName);

        if ($progressDataSet === null) {
            return 0;
        }

        return $progressDataSet->getTotal();
    }
}
