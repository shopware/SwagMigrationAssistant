<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ManufacturerAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class ProductDataSelection implements DataSelectionInterface
{
    public const IDENTIFIER = 'products';

    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            self::IDENTIFIER,
            $this->getEntityNames(),
            'swag-migration.index.selectDataCard.dataSelection.products',
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
            MediaFolderDataSet::getEntity(),
            ProductAttributeDataSet::getEntity(),
            ProductPriceAttributeDataSet::getEntity(),
            ManufacturerAttributeDataSet::getEntity(),
            ProductDataSet::getEntity(),
            PropertyGroupOptionDataSet::getEntity(),
            TranslationDataSet::getEntity(),
        ];
    }
}