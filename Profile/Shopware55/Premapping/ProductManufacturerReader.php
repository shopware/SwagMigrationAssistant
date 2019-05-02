<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Premapping;

use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Premapping\AbstractPremappingReader;
use SwagMigrationNext\Migration\Premapping\PremappingChoiceStruct;
use SwagMigrationNext\Migration\Premapping\PremappingEntityStruct;
use SwagMigrationNext\Migration\Premapping\PremappingStruct;
use SwagMigrationNext\Profile\Shopware55\DataSelection\ProductDataSelection;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class ProductManufacturerReader extends AbstractPremappingReader
{
    private const MAPPING_NAME = 'manufacturer';

    /**
     * @var EntityRepositoryInterface
     */
    private $manufacturerRepo;

    /**
     * @var string
     */
    private $connectionPremappingValue = '';

    public function __construct(EntityRepositoryInterface $manufacturerRepo)
    {
        $this->manufacturerRepo = $manufacturerRepo;
    }

    public static function getMappingName(): string
    {
        return self::MAPPING_NAME;
    }

    /**
     * @param string[] $entityGroupNames
     */
    public function supports(string $profileName, string $gatewayIdentifier, array $entityGroupNames): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME
            && in_array(ProductDataSelection::IDENTIFIER, $entityGroupNames, true);
    }

    public function getPremapping(Context $context, MigrationContext $migrationContext): PremappingStruct
    {
        $this->fillConnectionPremappingValue($migrationContext);
        $mapping = $this->getMapping();
        $choices = $this->getChoices($context);

        return new PremappingStruct(self::getMappingName(), $mapping, $choices);
    }

    protected function fillConnectionPremappingValue(MigrationContext $migrationContext): void
    {
        if ($migrationContext->getConnection()->getPremapping() === null) {
            return;
        }

        foreach ($migrationContext->getConnection()->getPremapping() as $premapping) {
            if ($premapping['entity'] === self::MAPPING_NAME) {
                foreach ($premapping['mapping'] as $mapping) {
                    $this->connectionPremappingValue = $mapping['destinationUuid'];
                }
            }
        }
    }

    /**
     * @return PremappingEntityStruct[]
     */
    private function getMapping(): array
    {
        $entityData[] = new PremappingEntityStruct('default_manufacturer', 'Standard manufacturer', $this->connectionPremappingValue);

        return $entityData;
    }

    /**
     * @return PremappingChoiceStruct[]
     */
    private function getChoices(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('name'));
        $manufacturers = $this->manufacturerRepo->search($criteria, $context);

        $choices = [];
        /** @var ProductManufacturerEntity $manufacturer */
        foreach ($manufacturers as $manufacturer) {
            $choices[] = new PremappingChoiceStruct($manufacturer->getId(), $manufacturer->getName());
        }

        return $choices;
    }
}
