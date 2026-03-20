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
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CategoryAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CategoryConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CrossSellingConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CurrencyConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerGroupAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerGroupConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54CustomerWishlistConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54LanguageConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54MainVariantRelationConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ManufacturerAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54MediaConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54MediaFolderConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54NewsletterRecipientConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54NumberRangeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderDocumentAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54OrderDocumentConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductOptionRelationConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductPriceAttributeConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductPropertyRelationConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ProductReviewConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54PropertyGroupOptionConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54SalesChannelConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54SeoUrlConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54ShippingMethodConverter;
use SwagMigrationAssistant\Profile\Shopware54\Converter\Shopware54TranslationConverter;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(Shopware54Profile::class)
        ->tag('shopware.migration.profile');

    $services->set(Shopware54ProductAttributeConverter::class)
        ->parent(ProductAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54ProductPriceAttributeConverter::class)
        ->parent(ProductPriceAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54ManufacturerAttributeConverter::class)
        ->parent(ManufacturerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CustomerAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54OrderAttributeConverter::class)
        ->parent(OrderAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54ProductConverter::class)
        ->parent(ProductConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54ProductOptionRelationConverter::class)
        ->parent(ProductOptionRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54ProductPropertyRelationConverter::class)
        ->parent(ProductPropertyRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54TranslationConverter::class)
        ->parent(TranslationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CategoryAttributeConverter::class)
        ->parent(CategoryAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CategoryConverter::class)
        ->parent(CategoryConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54MediaFolderConverter::class)
        ->parent(MediaFolderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54MediaConverter::class)
        ->parent(MediaConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CustomerConverter::class)
        ->parent(CustomerConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CustomerWishlistConverter::class)
        ->parent(CustomerWishlistConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54OrderConverter::class)
        ->parent(OrderConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54OrderDocumentAttributeConverter::class)
        ->parent(OrderDocumentAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54OrderDocumentConverter::class)
        ->parent(OrderDocumentConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CustomerGroupAttributeConverter::class)
        ->parent(CustomerAttributeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CustomerGroupConverter::class)
        ->parent(CustomerGroupConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54PropertyGroupOptionConverter::class)
        ->parent(PropertyGroupOptionConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54NumberRangeConverter::class)
        ->parent(NumberRangeConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CurrencyConverter::class)
        ->parent(CurrencyConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54LanguageConverter::class)
        ->parent(LanguageConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54SalesChannelConverter::class)
        ->parent(SalesChannelConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54NewsletterRecipientConverter::class)
        ->parent(NewsletterRecipientConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54ShippingMethodConverter::class)
        ->parent(ShippingMethodConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54ProductReviewConverter::class)
        ->parent(ProductReviewConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54SeoUrlConverter::class)
        ->parent(SeoUrlConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54CrossSellingConverter::class)
        ->parent(CrossSellingConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54MainVariantRelationConverter::class)
        ->parent(MainVariantRelationConverter::class)
        ->tag('shopware.migration.converter');

    $services->set(Shopware54PromotionConverter::class)
        ->parent(PromotionConverter::class)
        ->tag('shopware.migration.converter');
};
