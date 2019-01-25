<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use SwagMigrationNext\Migration\MigrationContext;

class DataSelectionRegistry implements DataSelectionRegistryInterface
{
    /**
     * @var DataSelectionInterface[]
     */
    private $dataSelections;

    public function __construct(iterable $dataSelections)
    {
        $this->dataSelections = $dataSelections;
    }

    public function getDataSelections(MigrationContext $migrationContext): DataSelectionCollection
    {
        $resultDataSelections = new DataSelectionCollection();
        foreach ($this->dataSelections as $dataSelection) {
            if ($dataSelection->supports($migrationContext->getProfileName(), $migrationContext->getGateway())) {
                $resultDataSelections->add($dataSelection->getData());
            }
        }

        $resultDataSelections->sort(function (DataSelectionStruct $first, DataSelectionStruct $second) {
            return $first->getPosition() > $second->getPosition();
        });

        return $resultDataSelections;
    }
}
