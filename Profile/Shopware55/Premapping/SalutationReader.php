<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Premapping;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\Salutation\SalutationEntity;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationNext\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationNext\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Profile\Shopware55\Gateway\TableReaderFactory;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class SalutationReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'salutation';

    /**
     * @var string[]
     */
    protected $preselectionDictionary = [];

    /**
     * @var EntityRepositoryInterface
     */
    private $salutationRepo;

    public function __construct(EntityRepositoryInterface $salutationRepo)
    {
        $this->salutationRepo = $salutationRepo;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME
            && in_array('customersOrders', $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContext $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingDictionary($migrationContext);
        $mapping = $this->getMapping($migrationContext);
        $choices = $this->getChoices($context);
        $this->setPreselection($mapping);

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(MigrationContext $migrationContext): array
    {
        $readerFactory = new TableReaderFactory();
        $reader = $readerFactory->create($migrationContext);

        if ($reader === null) {
            return [];
        }

        $result = $reader->read('s_core_config_elements', ['name' => 'shopsalutations']);
        if (empty($result)) {
            return [];
        }

        $salutations[] = explode(',', unserialize($result[0]['value'], ['allowed_classes' => false]));

        if (empty($salutations)) {
            return [];
        }

        $configuredSalutations = $reader->read('s_core_config_values', ['element_id' => $result[0]['id']]);

        if (!empty($configuredSalutations)) {
            foreach ($configuredSalutations as $configuredSalutation) {
                $salutations[] = explode(',', unserialize($configuredSalutation['value'], ['allowed_classes' => false]));
            }
        }

        $salutations = array_values(array_unique(array_merge(...$salutations)));
        $entityData = [];

        foreach ($salutations as $salutation) {
            $uuid = '';
            if (isset($this->connectionPremappingDictionary[$salutation])) {
                $uuid = $this->connectionPremappingDictionary[$salutation]['destinationUuid'];
            }

            $entityData[] = new PremappingEntityStruct($salutation, $salutation, $uuid);
        }

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('salutationKey'));
        $salutations = $this->salutationRepo->search($criteria, $context);

        $choices = [];
        /** @var SalutationEntity $salutation */
        foreach ($salutations as $salutation) {
            $this->preselectionDictionary[$salutation->getSalutationKey()] = $salutation->getId();
            $choices[] = new PremappingChoiceStruct($salutation->getId(), $salutation->getSalutationKey());
        }

        return $choices;
    }

    /**
     * @param PremappingEntityStruct[] $mapping
     */
    private function setPreselection(array $mapping): void
    {
        foreach ($mapping as $item) {
            if ($item->getDestinationUuid() !== '' || !isset($this->preselectionDictionary[$item->getSourceId()])) {
                continue;
            }

            $item->setDestinationUuid($this->preselectionDictionary[$item->getSourceId()]);
        }
    }
}
