<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigDefinition;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Promotion\PromotionDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Content\MailTemplate\Aggregate\MailHeaderFooter\MailHeaderFooterDefinition;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Product\Aggregate\ProductFeatureSet\ProductFeatureSetDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingDefinition;
use Shopware\Core\Content\ProductStream\Aggregate\ProductStreamFilter\ProductStreamFilterDefinition;
use Shopware\Core\Content\ProductStream\ProductStreamDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\DeliveryTime\DeliveryTimeDefinition;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\Snippet\Aggregate\SnippetSet\SnippetSetDefinition;
use Shopware\Core\System\Snippet\SnippetDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleDefinition;
use Shopware\Core\System\Tax\TaxDefinition;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CmsPageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CurrencyLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DeliveryTimeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DocumentTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\GlobalDocumentBaseConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MailTemplateTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaDefaultFolderLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaThumbnailSizeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\ProductSortingLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalesChannelLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalesChannelTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalutationLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SeoUrlTemplateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\StateMachineStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemDefaultMailTemplateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxRuleTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingService;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\Media\Processor\BaseMediaService;
use SwagMigrationAssistant\Migration\Media\Processor\HttpDownloadServiceBase;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CategoryAssociationConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CategoryCmsPageAssociationConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CategoryProductStreamAssociationConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CmsPageConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CountryConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CountryStateConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CustomerGroupConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CustomerWishlistConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\CustomFieldSetConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\DeliveryTimeConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\DocumentBaseConfigConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\DocumentConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\DocumentInheritanceConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\MailHeaderFooterConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\MailTemplateConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\MediaConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\MediaFolderInheritanceConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\OrderConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\PageSystemConfigConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductFeatureSetConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductManufacturerConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductSortingConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductStreamConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ProductStreamFilterInheritanceConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\PropertyGroupConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\RuleConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SalesChannelDomainConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SalutationConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SeoUrlConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SeoUrlTemplateConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ShopwareConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\ShopwareMediaConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SnippetConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SnippetSetConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\SystemConfigConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\TaxConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\TaxRuleConverter;
use SwagMigrationAssistant\Profile\Shopware6\Converter\UnitConverter;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\BasicSettingsDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\CmsDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryAssociationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryCmsPageAssociationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryProductStreamAssociationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CmsPageDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CountryDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CountryStateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CrossSellingDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerWishlistDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomFieldSetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DeliveryTimeDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentBaseConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\DocumentInheritanceDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MailHeaderFooterDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MailTemplateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\MediaFolderInheritanceDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\NewsletterRecipientDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\OrderDocumentGeneratedDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\PageSystemConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductDownloadDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductFeatureSetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductManufacturerDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductReviewDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductSortingDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductStreamDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ProductStreamFilterInheritanceDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\PromotionDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\PropertyGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\RuleDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalesChannelDomainDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalutationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SeoUrlDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SeoUrlTemplateDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SnippetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SnippetSetDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SystemConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\TaxDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\TaxRuleDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\UnitDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\MediaDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\NewsletterRecipientDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\ProductReviewDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\PromotionDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\SeoUrlDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\WishlistDataSelection;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ApiReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CategoryAssociationReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CategoryCmsPageAssociationReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CategoryProductStreamAssociationReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CategoryReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CmsPageReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CountryReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CountryStateReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CrossSellingReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CurrencyReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CustomerGroupReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CustomerReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CustomerWishlistReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\CustomFieldSetReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\DeliveryTimeReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\DocumentBaseConfigReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\DocumentInheritanceReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\DocumentReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\LanguageReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\MailHeaderFooterReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\MailTemplateReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\MediaFolderInheritanceReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\MediaFolderReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\MediaReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\NewsletterRecipientReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\NumberRangeReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\OrderReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\PageSystemConfigReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ProductFeatureSetReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ProductManufacturerReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ProductReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ProductReviewReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ProductSortingReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ProductStreamFilterInheritanceReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ProductStreamReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\PromotionReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\PropertyGroupReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\RuleReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SalesChannelDomainReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SalesChannelReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SeoUrlReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SeoUrlTemplateReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\ShippingMethodReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SnippetReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SnippetSetReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\SystemConfigReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\TaxReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\TaxRuleReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\TotalReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Reader\UnitReader;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Api\Shopware6ApiGateway;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware6\Media\HttpMediaDownloadService;
use SwagMigrationAssistant\Profile\Shopware6\Media\HttpOrderDocumentDownloadService;
use SwagMigrationAssistant\Profile\Shopware6\Media\HttpOrderDocumentGenerationService;
use SwagMigrationAssistant\Profile\Shopware6\Media\HttpProductDownloadService;
use SwagMigrationAssistant\Profile\Shopware6\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware6\Premapping\UserReader;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;
use SwagMigrationAssistant\Profile\Shopware6\Writer\CategoryAssociationWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\CategoryCmsPageAssociationWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\CategoryProductStreamAssociationWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\CmsPageWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\CountryStateWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\CustomFieldSetWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\DeliveryTimeWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\DocumentBaseConfigWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\DocumentInheritanceWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\MailHeaderFooterWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\MailTemplateWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\MediaFolderInheritanceWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\PageSystemConfigWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\ProductFeatureSetWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\ProductManufacturerWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\ProductSortingWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\ProductStreamFilterInheritanceWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\ProductStreamWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\PromotionWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\PropertyGroupWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\RuleWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\SalesChannelDomainWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\SalutationWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\SeoUrlTemplateWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\SnippetSetWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\SnippetWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\SystemConfigWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\TaxRuleWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\TaxWriter;
use SwagMigrationAssistant\Profile\Shopware6\Writer\UnitWriter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ConnectionFactory::class)
        ->args([
            service('swag_migration_connection.repository'),
            service(MigrationConfiguration::class),
        ]);

    $services->set(Shopware6ApiGateway::class)
        ->args([
            service(ReaderRegistry::class),
            service(EnvironmentReader::class),
            service('currency.repository'),
            service('language.repository'),
            service(TotalReader::class),
            service(TableReader::class),
            '%kernel.shopware_version%',
        ])
        ->tag('shopware.migration.gateway');

    $services->set(Shopware6MajorProfile::class)
        ->args(['%kernel.shopware_version%'])
        ->tag('shopware.migration.profile');

    $services->set(ShopwareConverter::class)
        ->abstract()
        ->args([
            service(MappingService::class),
            service(LoggingService::class),
        ]);

    $services->set(ShopwareMediaConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([service(MediaFileService::class)]);

    $services->set(ProductManufacturerConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(LanguageConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([
            service(LanguageLookup::class),
            service(LocaleLookup::class),
        ])
        ->tag('shopware.migration.converter');

    $services->set(DeliveryTimeConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(DeliveryTimeLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(CurrencyConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(CurrencyLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(CategoryConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CategoryAssociationConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CategoryProductStreamAssociationConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CategoryCmsPageAssociationConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(MediaFolderConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([
            service(MediaDefaultFolderLookup::class),
            service(MediaThumbnailSizeLookup::class),
        ])
        ->tag('shopware.migration.converter');

    $services->set(MediaFolderInheritanceConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(TaxConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(TaxLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(TaxRuleConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([
            service(TaxRuleLookup::class),
            service(TaxRuleTypeLookup::class),
        ])
        ->tag('shopware.migration.converter');

    $services->set(PropertyGroupConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(ProductConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(OrderConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(StateMachineStateLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(UnitConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(RuleConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CountryConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(CountryLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(CountryStateConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(CountryStateLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(SalesChannelConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([
            service(SalesChannelTypeLookup::class),
            service(SalesChannelLookup::class),
        ])
        ->tag('shopware.migration.converter');

    $services->set(SalesChannelDomainConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(SalutationConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(SalutationLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(ShippingMethodConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->args([service('shipping_method.repository')])
        ->tag('shopware.migration.converter');

    $services->set(CustomerGroupConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CustomFieldSetConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(NumberRangeConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([
            service('number_range_state.repository'),
            service(NumberRangeLookup::class),
            service(NumberRangeTypeLookup::class),
        ])
        ->tag('shopware.migration.converter');

    $services->set(MailHeaderFooterConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(MailTemplateConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->args([
            service(MailTemplateTypeLookup::class),
            service(SystemDefaultMailTemplateLookup::class),
        ])
        ->tag('shopware.migration.converter');

    $services->set(SnippetConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(SnippetSetConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(ProductFeatureSetConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(MediaConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(SeoUrlTemplateConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(SeoUrlTemplateLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(SeoUrlConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(NewsletterRecipientConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CustomerConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CustomerWishlistConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(SystemConfigConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(SystemConfigLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(PageSystemConfigConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(SystemConfigLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(ProductSortingConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(ProductSortingLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(CmsPageConverter::class)
        ->parent(ShopwareConverter::class)
        ->args([service(CmsPageLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(ProductStreamConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(ProductStreamFilterInheritanceConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(CrossSellingConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(ProductReviewConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(DocumentConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->args([service(DocumentTypeLookup::class)])
        ->tag('shopware.migration.converter');

    $services->set(DocumentInheritanceConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(DocumentBaseConfigConverter::class)
        ->parent(ShopwareMediaConverter::class)
        ->args([
            service(DocumentTypeLookup::class),
            service(GlobalDocumentBaseConfigLookup::class),
        ])
        ->tag('shopware.migration.converter');

    $services->set(PromotionConverter::class)
        ->parent(ShopwareConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(EnvironmentReader::class)
        ->args([service(ConnectionFactory::class)]);

    $services->set(TableReader::class)
        ->args([service(ConnectionFactory::class)]);

    $services->set(TotalReader::class)
        ->args([service(ConnectionFactory::class)]);

    $services->set(ApiReader::class)
        ->abstract()
        ->args([service(ConnectionFactory::class)]);

    $services->set(ProductDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(BasicSettingsDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(MediaDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(NewsletterRecipientDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(SeoUrlDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(CustomerAndOrderDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(WishlistDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(CmsDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(PromotionDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(ProductReviewDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(ProductManufacturerDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(LanguageDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CategoryDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CategoryAssociationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CategoryCmsPageAssociationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CategoryProductStreamAssociationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CurrencyDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(DeliveryTimeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(OrderDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(PropertyGroupDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(TaxDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(TaxRuleDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(UnitDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(RuleDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CountryDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CountryStateDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerGroupDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomFieldSetDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MediaFolderDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MediaFolderInheritanceDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(NumberRangeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SalutationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SalesChannelDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SalesChannelDomainDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ShippingMethodDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SnippetDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SnippetSetDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MailHeaderFooterDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MailTemplateDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SystemConfigDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductFeatureSetDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerWishlistDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MediaDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SeoUrlTemplateDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SeoUrlDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(NewsletterRecipientDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CmsPageDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductStreamDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductStreamFilterInheritanceDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CrossSellingDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(PageSystemConfigDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(PromotionDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductSortingDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductReviewDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(DocumentDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(DocumentInheritanceDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(DocumentBaseConfigDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(OrderDocumentGeneratedDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductDownloadDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(PaymentMethodReader::class)
        ->args([
            service('payment_method.repository'),
            service(GatewayRegistry::class),
        ])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(UserReader::class)
        ->args([
            service('user.repository'),
            service(GatewayRegistry::class),
        ])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(CategoryAssociationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CategoryProductStreamAssociationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CategoryCmsPageAssociationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CategoryReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CurrencyReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(LanguageReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductManufacturerReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductReviewReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(PropertyGroupReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(TaxReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(TaxRuleReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(UnitReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(MediaFolderReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(MediaFolderInheritanceReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(RuleReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CountryReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CountryStateReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(DeliveryTimeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CustomerGroupReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(NumberRangeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(OrderReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CustomFieldSetReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SalesChannelReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SalesChannelDomainReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SalutationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ShippingMethodReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SnippetReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SnippetSetReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(MailHeaderFooterReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(MailTemplateReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductFeatureSetReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(MediaReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SeoUrlTemplateReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SeoUrlReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(NewsletterRecipientReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CustomerReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CustomerWishlistReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(SystemConfigReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(PageSystemConfigReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductSortingReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CmsPageReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductStreamReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductStreamFilterInheritanceReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CrossSellingReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(DocumentReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(DocumentInheritanceReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(DocumentBaseConfigReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(PromotionReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductManufacturerWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductManufacturerDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CategoryAssociationWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CategoryDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CategoryProductStreamAssociationWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CategoryDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CategoryCmsPageAssociationWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CategoryDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(PropertyGroupWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(PropertyGroupDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(TaxWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(TaxDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(TaxRuleWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(TaxRuleDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(UnitWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(UnitDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SalutationWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SalutationDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(RuleWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(RuleDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CustomFieldSetWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(MediaFolderInheritanceWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(MediaFolderDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SnippetWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SnippetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SnippetSetWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SnippetSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(MailHeaderFooterWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(MailHeaderFooterDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(MailTemplateWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(MailTemplateDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(DeliveryTimeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(DeliveryTimeDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductFeatureSetWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductFeatureSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SalesChannelDomainWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SalesChannelDomainDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SeoUrlTemplateWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SeoUrlTemplateDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SystemConfigWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SystemConfigDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(PageSystemConfigWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SystemConfigDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductSortingWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductSortingDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CmsPageWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CmsPageDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductStreamWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductStreamDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductStreamFilterInheritanceWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductStreamFilterDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(PromotionWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(PromotionDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(DocumentInheritanceWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(DocumentDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(DocumentBaseConfigWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(DocumentBaseConfigDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CountryStateWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CountryStateDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(HttpMediaDownloadService::class)
        ->parent(HttpDownloadServiceBase::class)
        ->tag('shopware.migration.media_file_processor');

    $services->set(HttpOrderDocumentDownloadService::class)
        ->parent(HttpDownloadServiceBase::class)
        ->args([
            service(ConnectionFactory::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('shopware.migration.media_file_processor');

    $services->set(HttpProductDownloadService::class)
        ->parent(HttpDownloadServiceBase::class)
        ->args([
            service(ConnectionFactory::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('shopware.migration.media_file_processor');

    $services->set(HttpOrderDocumentGenerationService::class)
        ->parent(BaseMediaService::class)
        ->args([
            service('document.repository'),
            service('swag_migration_media_file.repository'),
            service(LoggingService::class),
            service(MappingService::class),
            service(MediaService::class),
            service(ConnectionFactory::class),
            service(Connection::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('shopware.migration.media_file_processor');
};
