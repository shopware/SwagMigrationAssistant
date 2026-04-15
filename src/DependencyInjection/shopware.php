<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Checkout\Promotion\PromotionDefinition;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\Gateway\GatewayRegistry;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderRegistry;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CountryStateLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\CurrencyLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DefaultCmsPageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DeliveryTimeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\DocumentTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\GlobalDocumentBaseConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LocaleLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LowestRootCategoryLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaDefaultFolderLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\MediaThumbnailSizeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\TaxLookup;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\Media\Processor\BaseMediaService;
use SwagMigrationAssistant\Migration\Media\Processor\HttpDownloadServiceBase;
use SwagMigrationAssistant\Migration\MigrationConfiguration;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;
use SwagMigrationAssistant\Profile\Shopware\Converter\AttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerGroupConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerWishlistConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\MainVariantRelationConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\MediaConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderDocumentAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductOptionRelationConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductPropertyRelationConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\SeoUrlConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\ShopwareConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\BasicSettingsDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CategoryDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CrossSellingDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CurrencyDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerGroupAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerGroupDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\CustomerWishlistDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\LanguageDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MainVariantRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ManufacturerAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaFolderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NewsletterRecipientDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDownloadDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductOptionRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductPriceAttributeDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductPropertyRelationDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductReviewDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PromotionDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PropertyGroupOptionDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SalesChannelDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\SeoUrlDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ShippingMethodDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\TranslationDataSet;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\MediaDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\NewsletterRecipientDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\ProductReviewDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\PromotionDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\SeoUrlDataSelection;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\WishlistDataSelection;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ApiReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableCountReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\AbstractReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\AttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CategoryAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CategoryReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CrossSellingReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CurrencyReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerGroupAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerGroupReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\CustomerWishlistReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\EnvironmentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\LanguageReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\MainVariantRelationReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ManufacturerAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\MediaAlbumReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\MediaReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\NewsletterRecipientReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\NumberRangeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderDocumentAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderDocumentReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\OrderReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductOptionRelationReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductPriceAttributeReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductPropertyRelationReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ProductReviewReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\PromotionReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\PropertyGroupOptionReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\SalesChannelReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\SeoUrlReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\ShippingMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TableReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\TranslationReader;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Media\HttpEsdFileDownloadService;
use SwagMigrationAssistant\Profile\Shopware\Media\HttpMediaDownloadService;
use SwagMigrationAssistant\Profile\Shopware\Media\HttpOrderDocumentDownloadService;
use SwagMigrationAssistant\Profile\Shopware\Media\LocalMediaProcessor;
use SwagMigrationAssistant\Profile\Shopware\Media\LocalOrderDocumentProcessor;
use SwagMigrationAssistant\Profile\Shopware\Media\LocalProductDownloadProcessor;
use SwagMigrationAssistant\Profile\Shopware\Media\Strategy\Md5StrategyResolver;
use SwagMigrationAssistant\Profile\Shopware\Media\Strategy\PlainStrategyResolver;
use SwagMigrationAssistant\Profile\Shopware\Premapping\DefaultShippingAvailabilityRuleReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\DeliveryTimeReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\NewsletterRecipientStatusReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderDeliveryStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\OrderStateReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\PaymentMethodReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\SalutationReader;
use SwagMigrationAssistant\Profile\Shopware\Premapping\TransactionStateReader;
use SwagMigrationAssistant\Profile\Shopware\Writer\ProductOptionRelationWriter;
use SwagMigrationAssistant\Profile\Shopware\Writer\ProductPropertyRelationWriter;
use SwagMigrationAssistant\Profile\Shopware\Writer\PromotionWriter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ConnectionFactory::class)
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(ShopwareLocalGateway::class)
        ->args([
            service(ReaderRegistry::class),
            service(EnvironmentReader::class),
            service(TableReader::class),
            service(ConnectionFactory::class),
            service('currency.repository'),
            service('language.repository'),
        ])
        ->tag('shopware.migration.gateway');

    $services->set(ShopwareApiGateway::class)
        ->args([
            service(ReaderRegistry::class),
            service(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader::class),
            service(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableReader::class),
            service(TableCountReader::class),
            service('currency.repository'),
            service('language.repository'),
        ])
        ->tag('shopware.migration.gateway');

    $services->set(BaseMediaService::class)
        ->abstract();

    $services->set(HttpMediaDownloadService::class)
        ->parent(HttpDownloadServiceBase::class)
        ->tag('shopware.migration.media_file_processor');

    $services->set(LocalMediaProcessor::class)
        ->parent(BaseMediaService::class)
        ->args([
            service('swag_migration_media_file.repository'),
            service(FileSaver::class),
            service(LoggingService::class),
            tagged_iterator('shopware.migration.media_strategy_resolver'),
            service(Connection::class),
        ])
        ->tag('shopware.migration.media_file_processor');

    $services->set(Md5StrategyResolver::class)
        ->tag('shopware.migration.media_strategy_resolver');

    $services->set(PlainStrategyResolver::class)
        ->tag('shopware.migration.media_strategy_resolver');

    $services->set(HttpOrderDocumentDownloadService::class)
        ->parent(HttpDownloadServiceBase::class)
        ->args([
            service(ConnectionFactory::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('shopware.migration.media_file_processor');

    $services->set(LocalOrderDocumentProcessor::class)
        ->parent(BaseMediaService::class)
        ->args([
            service('swag_migration_media_file.repository'),
            service(MediaService::class),
            service(LoggingService::class),
            service(Connection::class),
        ])
        ->tag('shopware.migration.media_file_processor');

    $services->set(LocalProductDownloadProcessor::class)
        ->parent(BaseMediaService::class)
        ->args([
            service('swag_migration_media_file.repository'),
            service(MediaService::class),
            service(LoggingService::class),
            service(Connection::class),
        ])
        ->tag('shopware.migration.media_file_processor');

    $services->set(HttpEsdFileDownloadService::class)
        ->parent(HttpDownloadServiceBase::class)
        ->args([
            service(ConnectionFactory::class),
            service(MigrationConfiguration::class),
        ])
        ->tag('shopware.migration.media_file_processor');

    $services->set(ShopwareConverter::class)
        ->abstract()
        ->parent(Converter::class);

    $services->set(AttributeConverter::class)
        ->abstract()
        ->parent(Converter::class);

    $services->set(ProductAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(ProductPriceAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(ManufacturerAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(CustomerAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(OrderAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(OrderDocumentAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(ProductConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(MediaFileService::class),
            service(TaxLookup::class),
            service(MediaDefaultFolderLookup::class),
            service(LanguageLookup::class),
            service(DeliveryTimeLookup::class),
        ]);

    $services->set(ProductOptionRelationConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class);

    $services->set(ProductPropertyRelationConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class);

    $services->set(TranslationConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([service(LanguageLookup::class)]);

    $services->set(CategoryAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(CategoryConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(MediaFileService::class),
            service(LowestRootCategoryLookup::class),
            service(DefaultCmsPageLookup::class),
            service(LanguageLookup::class),
        ]);

    $services->set(MediaFolderConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(MediaDefaultFolderLookup::class),
            service(MediaThumbnailSizeLookup::class),
        ]);

    $services->set(MediaConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(MediaFileService::class),
            service(LanguageLookup::class),
        ]);

    $services->set(CustomerConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service('validator'),
            service('sales_channel.repository'),
            service(CountryLookup::class),
            service(LanguageLookup::class),
            service(CountryStateLookup::class),
        ]);

    $services->set(OrderConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(TaxCalculator::class),
            service('sales_channel.repository'),
            service(CountryLookup::class),
            service(CurrencyLookup::class),
            service(LanguageLookup::class),
            service(CountryStateLookup::class),
        ]);

    $services->set(OrderDocumentConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(MediaFileService::class),
            service(MediaDefaultFolderLookup::class),
            service(DocumentTypeLookup::class),
            service(GlobalDocumentBaseConfigLookup::class),
        ]);

    $services->set(CustomerGroupAttributeConverter::class)
        ->abstract()
        ->parent(AttributeConverter::class);

    $services->set(CustomerGroupConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([service(LanguageLookup::class)]);

    $services->set(CustomerWishlistConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class);

    $services->set(PropertyGroupOptionConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(MediaFileService::class),
            service(LanguageLookup::class),
        ]);

    $services->set(NumberRangeConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service('number_range_type.repository'),
            service(NumberRangeLookup::class),
            service(LanguageLookup::class),
        ]);

    $services->set(CurrencyConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(CurrencyLookup::class),
            service(LanguageLookup::class),
        ]);

    $services->set(LanguageConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(LocaleLookup::class),
            service(LanguageLookup::class),
        ]);

    $services->set(SalesChannelConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service('payment_method.repository'),
            service('shipping_method.repository'),
            service('country.repository'),
            service('sales_channel.repository'),
            service('swag_language_pack_language.repository')->nullOnInvalid(),
            service(CurrencyLookup::class),
            service(LanguageLookup::class),
        ]);

    $services->set(NewsletterRecipientConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([service(LanguageLookup::class)]);

    $services->set(ShippingMethodConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([
            service(CountryLookup::class),
            service(LanguageLookup::class),
        ]);

    $services->set(ProductReviewConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([service(LanguageLookup::class)]);

    $services->set(SeoUrlConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([service(LanguageLookup::class)]);

    $services->set(CrossSellingConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class);

    $services->set(MainVariantRelationConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class);

    $services->set(PromotionConverter::class)
        ->abstract()
        ->parent(ShopwareConverter::class)
        ->args([service('sales_channel.repository')]);

    $services->set(ApiReader::class)
        ->abstract()
        ->args([service(ConnectionFactory::class)]);

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\EnvironmentReader::class)
        ->args([service(ConnectionFactory::class)]);

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TableReader::class)
        ->args([service(ConnectionFactory::class)]);

    $services->set(TableCountReader::class)
        ->args([
            service(ConnectionFactory::class),
            service(LoggingService::class),
        ]);

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CategoryAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CategoryReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CurrencyReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CustomerAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CustomerGroupAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CustomerGroupReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CustomerReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\LanguageReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ManufacturerAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\MediaAlbumReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\MediaReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\NewsletterRecipientReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\NumberRangeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\OrderAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\OrderDocumentAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\OrderDocumentReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\OrderReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductPriceAttributeReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductReviewReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\PromotionReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\PropertyGroupOptionReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\SalesChannelReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\SeoUrlReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ShippingMethodReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\TranslationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductOptionRelationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\ProductPropertyRelationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CrossSellingReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\MainVariantRelationReader::class)
        ->parent(ApiReader::class)
        ->tag('shopware.migration.reader');

    $services->set(\SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader\CustomerWishlistReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader');

    $services->set(AbstractReader::class)
        ->abstract()
        ->args([service(ConnectionFactory::class)]);

    $services->set(EnvironmentReader::class)
        ->parent(AbstractReader::class)
        ->args([service(ConnectionFactory::class)])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(TableReader::class)
        ->parent(AbstractReader::class)
        ->args([service(ConnectionFactory::class)])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(AttributeReader::class)
        ->abstract()
        ->parent(AbstractReader::class);

    $services->set(CategoryAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CategoryReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CurrencyReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(NewsletterRecipientReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CustomerGroupReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CustomerGroupAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(CustomerReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CustomerAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(LanguageReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(ManufacturerAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(MediaAlbumReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(MediaReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(NumberRangeReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(OrderReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(OrderAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(ProductOptionRelationReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(ProductPropertyRelationReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(ProductAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ProductPriceAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(PropertyGroupOptionReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SalesChannelReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(TranslationReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(OrderDocumentReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(OrderDocumentAttributeReader::class)
        ->parent(AttributeReader::class)
        ->tag('shopware.migration.reader');

    $services->set(ShippingMethodReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(ProductReviewReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(SeoUrlReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CrossSellingReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(MainVariantRelationReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(PromotionReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(CustomerWishlistReader::class)
        ->parent(AbstractReader::class)
        ->tag('shopware.migration.reader')
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set(BasicSettingsDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(ProductDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(CustomerAndOrderDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(WishlistDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(MediaDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(NewsletterRecipientDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(ProductReviewDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(SeoUrlDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(PromotionDataSelection::class)
        ->tag('shopware.migration.data_selection');

    $services->set(ProductAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductPriceAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ManufacturerAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductDownloadDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductOptionRelationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductPropertyRelationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(OrderAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(OrderDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CategoryAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CategoryDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerGroupAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerGroupDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CustomerWishlistDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MediaFolderDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MediaDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(NumberRangeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(TranslationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(PropertyGroupOptionDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(NewsletterRecipientDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(LanguageDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CurrencyDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SalesChannelDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(OrderDocumentAttributeDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(OrderDocumentDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ShippingMethodDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(ProductReviewDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(SeoUrlDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(CrossSellingDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(MainVariantRelationDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(PromotionDataSet::class)
        ->tag('shopware.migration.data_set');

    $services->set(OrderStateReader::class)
        ->args([
            service('state_machine.repository'),
            service('state_machine_state.repository'),
            service(GatewayRegistry::class),
        ])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(OrderDeliveryStateReader::class)
        ->args([
            service('state_machine.repository'),
            service('state_machine_state.repository'),
            service(GatewayRegistry::class),
        ])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(TransactionStateReader::class)
        ->args([
            service('state_machine.repository'),
            service('state_machine_state.repository'),
            service(GatewayRegistry::class),
        ])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(PaymentMethodReader::class)
        ->args([
            service('payment_method.repository'),
            service(GatewayRegistry::class),
        ])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(SalutationReader::class)
        ->args([
            service('salutation.repository'),
            service(GatewayRegistry::class),
        ])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(DeliveryTimeReader::class)
        ->args([service('delivery_time.repository')])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(DefaultShippingAvailabilityRuleReader::class)
        ->args([service('rule.repository')])
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(NewsletterRecipientStatusReader::class)
        ->tag('shopware.migration.pre_mapping_reader');

    $services->set(ProductOptionRelationWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductPropertyRelationWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(PromotionWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(PromotionDefinition::class),
        ])
        ->tag('shopware.migration.writer');
};
