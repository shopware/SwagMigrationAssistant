<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CategoryAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerGroupAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ManufacturerAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class ProductCategoryTranslationDataSelection implements DataSelectionInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            'categoriesProducts',
            $this->getEntityNames(),
            'swag-migration.index.selectDataCard.dataSelection.categoriesProducts',
            100,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityNames(): array
    {
        return [
            CategoryAttributeDataSet::getEntity(),
            CategoryDataSet::getEntity(),
            CustomerGroupAttributeDataSet::getEntity(),
            CustomerGroupDataSet::getEntity(),
            ProductAttributeDataSet::getEntity(),
            ProductPriceAttributeDataSet::getEntity(),
            ManufacturerAttributeDataSet::getEntity(),
            ProductDataSet::getEntity(),
            TranslationDataSet::getEntity(),
        ];
    }
}
