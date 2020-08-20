<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use Shopware\Core\Framework\Struct\Struct;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;

class DataSelectionStruct extends Struct
{
    public const BASIC_DATA_TYPE = 'basicData';
    public const PLUGIN_DATA_TYPE = 'pluginData';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string[]
     */
    protected $entityNames;

    /**
     * @var string[]
     */
    protected $entityNamesRequiredForCount;

    /**
     * @var int[]
     */
    protected $entityTotals = [];

    /**
     * @var int
     */
    protected $total = 0;

    /**
     * @var bool
     */
    protected $processMediaFiles;

    /**
     * @var string
     */
    protected $snippet;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var string
     */
    protected $dataType;

    /**
     * @var bool
     */
    protected $requiredSelection;

    /**
     * @param DataSet[] $dataSets
     * @param DataSet[] $dataSetsRequiredForCount
     */
    public function __construct(
        string $id,
        array $dataSets,
        array $dataSetsRequiredForCount,
        string $snippet,
        int $position,
        bool $processMediaFiles = false,
        string $dataType = self::BASIC_DATA_TYPE,
        bool $requiredSelection = false
    ) {
        $entityNameArray = [];
        foreach ($dataSets as $dataSet) {
            $entityNameArray[$dataSet::getEntity()] = $dataSet->getSnippet();
        }

        $entityNamesRequiredForCount = [];
        foreach ($dataSetsRequiredForCount as $dataSet) {
            $entityNamesRequiredForCount[] = $dataSet::getEntity();
        }

        $this->id = $id;
        $this->entityNames = $entityNameArray;
        $this->entityNamesRequiredForCount = $entityNamesRequiredForCount;
        $this->snippet = $snippet;
        $this->position = $position;
        $this->processMediaFiles = $processMediaFiles;
        $this->dataType = $dataType;
        $this->requiredSelection = $requiredSelection;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityNames(): array
    {
        return $this->entityNames;
    }

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
