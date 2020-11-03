<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class DataSelectionRegistry implements DataSelectionRegistryInterface
{
    /**
     * @var DataSelectionInterface[]
     */
    private $dataSelections;

    /**
     * @param DataSelectionInterface[] $dataSelections
     */
    public function __construct(iterable $dataSelections)
    {
        $this->dataSelections = $dataSelections;
    }

    public function getDataSelections(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation): DataSelectionCollection
    {
        $resultDataSelections = new DataSelectionCollection();
        foreach ($this->dataSelections as $dataSelection) {
            if ($dataSelection->supports($migrationContext)) {
                $data = $dataSelection->getData();
                $this->setTotals($data, $environmentInformation);

                if ($data->getTotal() > 0) {
                    $resultDataSelections->set($dataSelection->getData()->getId(), $data);
                }
            }
        }
        $resultDataSelections->sortByPosition();

        return $resultDataSelections;
    }

    public function getDataSelectionsByIds(MigrationContextInterface $migrationContext, EnvironmentInformation $environmentInformation, array $ids): DataSelectionCollection
    {
        $resultDataSelections = new DataSelectionCollection();
        $profileDataSelections = $this->getDataSelections($migrationContext, $environmentInformation);

        foreach ($ids as $id) {
            $dataSelection = $profileDataSelections->get($id);

            if (empty($dataSelection)) {
                continue;
            }

            $resultDataSelections->set($id, $dataSelection);
        }

        $resultDataSelections->sortByPosition();

        return $resultDataSelections;
    }

    private function setTotals(DataSelectionStruct $dataSelection, EnvironmentInformation $environmentInformation): void
    {
        $totals = $environmentInformation->getTotals();
        $entityTotals = [];

        foreach (\array_keys($dataSelection->getEntityNames()) as $entityName) {
            if (isset($totals[$entityName])) {
                $entityTotals[$entityName] = $totals[$entityName]->getTotal();
            }
        }
        $dataSelection->setTotal($dataSelection->getCountedTotal($totals));
        $dataSelection->setEntityTotals($entityTotals);
    }
}
