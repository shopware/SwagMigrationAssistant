<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\TotalStruct;

#[Package('services-settings')]
class DataSelectionStruct extends Struct
{
    final public const BASIC_DATA_TYPE = 'basicData';
    final public const PLUGIN_DATA_TYPE = 'pluginData';

    /**
     * @var string[]
     */
    protected array $entityNames;

    /**
     * @var string[]
     */
    protected array $entityNamesRequiredForCount;

    /**
     * @var int[]
     */
    protected array $entityTotals = [];

    protected int $total = 0;

    /**
     * @param DataSet[] $dataSets
     * @param DataSet[] $dataSetsRequiredForCount
     */
    public function __construct(
        protected string $id,
        protected array $dataSets,
        protected array $dataSetsRequiredForCount,
        protected string $snippet,
        protected int $position,
        protected bool $processMediaFiles = false,
        protected string $dataType = self::BASIC_DATA_TYPE,
        protected bool $requiredSelection = false
    ) {
        $entityNameArray = [];
        foreach ($dataSets as $dataSet) {
            $entityNameArray[$dataSet::getEntity()] = $dataSet->getSnippet();
        }

        $entityNamesRequiredForCount = [];
        foreach ($dataSetsRequiredForCount as $dataSet) {
            $entityNamesRequiredForCount[] = $dataSet::getEntity();
        }

        $this->entityNames = $entityNameArray;
        $this->entityNamesRequiredForCount = $entityNamesRequiredForCount;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array<string>
     */
    public function getEntityNames(): array
    {
        return $this->entityNames;
    }

    /**
     * @param array<string, TotalStruct> $totals
     */
    public function getCountedTotal(array $totals): int
    {
        $countedTotal = 0;
        foreach ($this->entityNamesRequiredForCount as $countedEntityName) {
            if (isset($totals[$countedEntityName])) {
                $countedTotal += $totals[$countedEntityName]->getTotal();
            }
        }

        return $countedTotal;
    }

    /**
     * @return array<string>
     */
    public function getEntityNamesRequiredForCount(): array
    {
        return $this->entityNamesRequiredForCount;
    }

    public function getProcessMediaFiles(): bool
    {
        return $this->processMediaFiles;
    }

    public function getSnippet(): string
    {
        return $this->snippet;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function isRequiredSelection(): bool
    {
        return $this->requiredSelection;
    }

    public function setRequiredSelection(bool $requiredSelection): void
    {
        $this->requiredSelection = $requiredSelection;
    }

    /**
     * @return int[]
     */
    public function getEntityTotals(): array
    {
        return $this->entityTotals;
    }

    /**
     * @param int[] $entityTotals
     */
    public function setEntityTotals(array $entityTotals): void
    {
        $this->entityTotals = $entityTotals;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }
}
