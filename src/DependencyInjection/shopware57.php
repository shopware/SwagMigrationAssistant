<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SwagMigrationAssistant\Profile\Shopware\Converter\CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware\Converter\CustomerConverter;
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
use SwagMigrationAssistant\Profile\Shopware\Converter\TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerGroupConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57CustomerWishlistConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57MainVariantRelationConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57MediaConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderDocumentAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductOptionRelationConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductPropertyRelationConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57SeoUrlConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware57\Converter\Shopware57TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware57\Shopware57Profile;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(Shopware57Profile::class)
        ->tag('shopware.migration.profile');

    $services->set(Shopware57ProductAttributeConverter::class)
        ->parent(ProductAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57ProductPriceAttributeConverter::class)
        ->parent(ProductPriceAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57ManufacturerAttributeConverter::class)
        ->parent(ManufacturerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CustomerAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57OrderAttributeConverter::class)
        ->parent(OrderAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57ProductConverter::class)
        ->parent(ProductConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57ProductOptionRelationConverter::class)
        ->parent(ProductOptionRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57ProductPropertyRelationConverter::class)
        ->parent(ProductPropertyRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57TranslationConverter::class)
        ->parent(TranslationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CategoryAttributeConverter::class)
        ->parent(CategoryAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CategoryConverter::class)
        ->parent(CategoryConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57MediaFolderConverter::class)
        ->parent(MediaFolderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57MediaConverter::class)
        ->parent(MediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CustomerConverter::class)
        ->parent(CustomerConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CustomerWishlistConverter::class)
        ->parent(CustomerWishlistConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57OrderConverter::class)
        ->parent(OrderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57OrderDocumentAttributeConverter::class)
        ->parent(OrderDocumentAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57OrderDocumentConverter::class)
        ->parent(OrderDocumentConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CustomerGroupAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CustomerGroupConverter::class)
        ->parent(CustomerGroupConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57PropertyGroupOptionConverter::class)
        ->parent(PropertyGroupOptionConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57PromotionConverter::class)
        ->parent(PromotionConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57NumberRangeConverter::class)
        ->parent(NumberRangeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57MainVariantRelationConverter::class)
        ->parent(MainVariantRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CurrencyConverter::class)
        ->parent(CurrencyConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57LanguageConverter::class)
        ->parent(LanguageConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57SalesChannelConverter::class)
        ->parent(SalesChannelConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57NewsletterRecipientConverter::class)
        ->parent(NewsletterRecipientConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57ShippingMethodConverter::class)
        ->parent(ShippingMethodConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57ProductReviewConverter::class)
        ->parent(ProductReviewConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57SeoUrlConverter::class)
        ->parent(SeoUrlConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware57CrossSellingConverter::class)
        ->parent(CrossSellingConverter::class)
        ->tag('shopware.migration.converter');
};
