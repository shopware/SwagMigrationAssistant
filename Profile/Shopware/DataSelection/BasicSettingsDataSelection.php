<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\DataSelection;

use SwagMigrationAssistant\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerGroupAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class BasicSettingsDataSelection implements DataSelectionInterface
{
    public const IDENTIFIER = 'basicSettings';

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            self::IDENTIFIER,
            $this->getEntityNames(),
            'swag-migration.index.selectDataCard.dataSelection.basicSettings',
            -100,
            true,
            DataSelectionStruct::BASIC_DATA_TYPE,
            true
        );
    }

    public function getEntityNames(): array
    {
        return [
            LanguageDataSet::getEntity(),
            CategoryAttributeDataSet::getEntity(),
            CategoryDataSet::getEntity(),
            CustomerGroupAttributeDataSet::getEntity(),
            CustomerGroupDataSet::getEntity(),
            CurrencyDataSet::getEntity(),
            SalesChannelDataSet::getEntity(),
        ];
    }
}
