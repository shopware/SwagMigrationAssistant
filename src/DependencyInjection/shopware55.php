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
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerGroupConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55CustomerWishlistConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MainVariantRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MediaConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderDocumentAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductOptionRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductPropertyRelationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55SeoUrlConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware55\Converter\Shopware55TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(Shopware55Profile::class)
        ->tag('shopware.migration.profile');

    $services->set(Shopware55ProductAttributeConverter::class)
        ->parent(ProductAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55ProductPriceAttributeConverter::class)
        ->parent(ProductPriceAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55ManufacturerAttributeConverter::class)
        ->parent(ManufacturerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CustomerAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55OrderAttributeConverter::class)
        ->parent(OrderAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55ProductConverter::class)
        ->parent(ProductConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55ProductOptionRelationConverter::class)
        ->parent(ProductOptionRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55ProductPropertyRelationConverter::class)
        ->parent(ProductPropertyRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55TranslationConverter::class)
        ->parent(TranslationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CategoryAttributeConverter::class)
        ->parent(CategoryAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CategoryConverter::class)
        ->parent(CategoryConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55MediaFolderConverter::class)
        ->parent(MediaFolderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55MediaConverter::class)
        ->parent(MediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CustomerConverter::class)
        ->parent(CustomerConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55OrderConverter::class)
        ->parent(OrderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55OrderDocumentAttributeConverter::class)
        ->parent(OrderDocumentAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55OrderDocumentConverter::class)
        ->parent(OrderDocumentConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CustomerGroupAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CustomerGroupConverter::class)
        ->parent(CustomerGroupConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CustomerWishlistConverter::class)
        ->parent(CustomerWishlistConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55PropertyGroupOptionConverter::class)
        ->parent(PropertyGroupOptionConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55NumberRangeConverter::class)
        ->parent(NumberRangeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CurrencyConverter::class)
        ->parent(CurrencyConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55LanguageConverter::class)
        ->parent(LanguageConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55SalesChannelConverter::class)
        ->parent(SalesChannelConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55NewsletterRecipientConverter::class)
        ->parent(NewsletterRecipientConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55ShippingMethodConverter::class)
        ->parent(ShippingMethodConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55ProductReviewConverter::class)
        ->parent(ProductReviewConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55SeoUrlConverter::class)
        ->parent(SeoUrlConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55CrossSellingConverter::class)
        ->parent(CrossSellingConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55MainVariantRelationConverter::class)
        ->parent(MainVariantRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware55PromotionConverter::class)
        ->parent(PromotionConverter::class)
        ->tag('shopware.migration.converter');
};
