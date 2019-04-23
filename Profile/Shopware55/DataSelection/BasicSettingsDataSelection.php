<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\DataSelection;

use SwagMigrationNext\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerGroupAttributeDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationNext\Profile\Shopware55\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class BasicSettingsDataSelection implements DataSelectionInterface
{
    public function supports(string $profileName, string $gatewayIdentifier): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            'basicSettings',
            $this->getEntityNames(),
            'swag-migration.index.selectDataCard.dataSelection.basicSettings',
            200
        );
    }

    public function getEntityNames(): array
    {
        return [
            LanguageDataSet::getEntity(),
            CustomerGroupAttributeDataSet::getEntity(),
            CustomerGroupDataSet::getEntity(),
            CurrencyDataSet::getEntity(),
            SalesChannelDataSet::getEntity(),
        ];
    }
}
