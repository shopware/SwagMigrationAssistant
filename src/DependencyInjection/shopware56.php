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
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerGroupConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56CustomerWishlistConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56MainVariantRelationConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56MediaConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderDocumentAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductOptionRelationConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductPropertyRelationConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56SeoUrlConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware56\Converter\Shopware56TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware56\Shopware56Profile;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(Shopware56Profile::class)
        ->tag('shopware.migration.profile');

    $services->set(Shopware56ProductAttributeConverter::class)
        ->parent(ProductAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56ProductPriceAttributeConverter::class)
        ->parent(ProductPriceAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56ManufacturerAttributeConverter::class)
        ->parent(ManufacturerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CustomerAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56OrderAttributeConverter::class)
        ->parent(OrderAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56ProductConverter::class)
        ->parent(ProductConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56ProductOptionRelationConverter::class)
        ->parent(ProductOptionRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56ProductPropertyRelationConverter::class)
        ->parent(ProductPropertyRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56TranslationConverter::class)
        ->parent(TranslationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CategoryAttributeConverter::class)
        ->parent(CategoryAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CategoryConverter::class)
        ->parent(CategoryConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56MediaFolderConverter::class)
        ->parent(MediaFolderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56MediaConverter::class)
        ->parent(MediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CustomerConverter::class)
        ->parent(CustomerConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CustomerWishlistConverter::class)
        ->parent(CustomerWishlistConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56OrderConverter::class)
        ->parent(OrderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56OrderDocumentAttributeConverter::class)
        ->parent(OrderDocumentAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56OrderDocumentConverter::class)
        ->parent(OrderDocumentConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CustomerGroupAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CustomerGroupConverter::class)
        ->parent(CustomerGroupConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56PropertyGroupOptionConverter::class)
        ->parent(PropertyGroupOptionConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56NumberRangeConverter::class)
        ->parent(NumberRangeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CurrencyConverter::class)
        ->parent(CurrencyConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56LanguageConverter::class)
        ->parent(LanguageConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56SalesChannelConverter::class)
        ->parent(SalesChannelConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56NewsletterRecipientConverter::class)
        ->parent(NewsletterRecipientConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56ShippingMethodConverter::class)
        ->parent(ShippingMethodConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56ProductReviewConverter::class)
        ->parent(ProductReviewConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56SeoUrlConverter::class)
        ->parent(SeoUrlConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56CrossSellingConverter::class)
        ->parent(CrossSellingConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56MainVariantRelationConverter::class)
        ->parent(MainVariantRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware56PromotionConverter::class)
        ->parent(PromotionConverter::class)
        ->tag('shopware.migration.converter');
};
