<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
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
            [
                CategoryDefinition::getEntityName(),
                ProductDefinition::getEntityName(),
                //'translation',
            ],
            'swag-migration.index.selectDataCard.dataSelection.categoriesProducts',
            100,
            true
        );
    }
}
