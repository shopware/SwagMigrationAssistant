<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\DataSelection;

use SwagMigrationNext\Migration\MigrationContextInterface;

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

    public function getDataSelections(MigrationContextInterface $migrationContext): DataSelectionCollection
    {
        $profileName = $migrationContext->getProfileName();
        $gatewayName = $migrationContext->getGatewayName();

        $resultDataSelections = new DataSelectionCollection();
        foreach ($this->dataSelections as $dataSelection) {
            if ($dataSelection->supports($profileName, $gatewayName)) {
                $resultDataSelections->set($dataSelection->getData()->getId(), $dataSelection->getData());
            }
        }
        $resultDataSelections->sortByPosition();

        return $resultDataSelections;
    }

    public function getDataSelectionsByIds(MigrationContextInterface $migrationContext, array $ids): DataSelectionCollection
    {
        $resultDataSelections = new DataSelectionCollection();
        $profileDataSelections = $this->getDataSelections($migrationContext);

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
}
