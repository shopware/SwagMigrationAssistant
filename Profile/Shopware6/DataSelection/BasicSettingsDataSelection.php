<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\DataSelection;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryAssociationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CountryDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CountryStateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomFieldSetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DeliveryTimeDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentBaseConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MailTemplateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MediaFolderInheritanceDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductSortingDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\RuleDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDomainDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalutationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SnippetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SnippetSetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SystemConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\UnitDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

#[Package('services-settings')]
class BasicSettingsDataSelection implements DataSelectionInterface
{
    final public const IDENTIFIER = 'basicSettings';

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            self::IDENTIFIER,
            $this->getDataSets(),
            $this->getDataSetsRequiredForCount(),
            'swag-migration.index.selectDataCard.dataSelection.basicSettings',
            -100,
            true,
            DataSelectionStruct::BASIC_DATA_TYPE,
            true
        );
    }

    public function getDataSets(): array
    {
        return [
            new LanguageDataSet(),
            new CurrencyDataSet(),
            new UnitDataSet(),
            new MediaFolderDataSet(),
            new MediaFolderInheritanceDataSet(),
            new CategoryDataSet(),
            new CategoryAssociationDataSet(),
            new CountryDataSet(),
            new CountryStateDataSet(),
            new SalutationDataSet(),
            new CustomerGroupDataSet(),
            new CustomFieldSetDataSet(),
            new RuleDataSet(),
            new SnippetSetDataSet(),
            new SnippetDataSet(),
            new MailTemplateDataSet(),
            new DeliveryTimeDataSet(),
            new ShippingMethodDataSet(),
            new SalesChannelDataSet(),
            new SalesChannelDomainDataSet(),
            new SystemConfigDataSet(),
            new ProductSortingDataSet(),
            new DocumentBaseConfigDataSet(),
            new NumberRangeDataSet(),
        ];
    }

    public function getDataSetsRequiredForCount(): array
    {
        return $this->getDataSets();
    }
}
