<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Store\Services\AbstractExtensionDataProvider;
use Shopware\Core\Framework\Store\Services\StoreClient;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use SwagMigrationAssistant\Controller\DataProviderController;
use SwagMigrationAssistant\DataProvider\Provider\Data\CategoryAssociationProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CategoryCmsPageAssociationProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CategoryProductStreamAssociationProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CategoryProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CmsPageProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CountryProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CountryStateProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CrossSellingProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CurrencyProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CustomerGroupProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CustomerProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CustomerWishlistProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\CustomFieldSetProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\DeliveryTimeProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\DocumentBaseConfigProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\DocumentInheritanceProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\DocumentProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\LanguageProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\MailHeaderFooterProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\MailTemplateProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\MediaFolderInheritanceProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\MediaFolderProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\MediaProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\NewsletterRecipientProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\NumberRangeProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\OrderProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\PageSystemConfigProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\PaymentMethodProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ProductFeatureSetProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ProductManufacturerProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ProductProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ProductReviewProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ProductSortingProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ProductStreamFilterInheritanceProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ProductStreamProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\PromotionProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\PropertyGroupProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\RuleProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SalesChannelDomainProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SalesChannelProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SalutationProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SeoUrlProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SeoUrlTemplateProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\ShippingMethodProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SnippetProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SnippetSetProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\SystemConfigProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\TaxProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\TaxRuleProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\UnitProvider;
use SwagMigrationAssistant\DataProvider\Provider\Data\UserProvider;
use SwagMigrationAssistant\DataProvider\Provider\ProviderRegistry;
use SwagMigrationAssistant\DataProvider\Service\EnvironmentService;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ProviderRegistry::class)
        ->args([tagged_iterator('shopware.dataProvider.provider')]);

    $services->set(DataProviderController::class)
        ->public()
        ->args([
            service(ProviderRegistry::class),
            service(EnvironmentService::class),
            service(DocumentGenerator::class),
            service('media.repository'),
            service(MediaService::class),
            service('shopware.filesystem.private'),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(ProductManufacturerProvider::class)
        ->args([service('product_manufacturer.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(LanguageProvider::class)
        ->args([service('language.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CategoryProvider::class)
        ->args([service('category.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CategoryAssociationProvider::class)
        ->args([service('category.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CategoryProductStreamAssociationProvider::class)
        ->args([service('category.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CategoryCmsPageAssociationProvider::class)
        ->args([service('category.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CurrencyProvider::class)
        ->args([service('currency.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(MediaFolderProvider::class)
        ->args([service('media_folder.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(MediaFolderInheritanceProvider::class)
        ->args([service('media_folder.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(DeliveryTimeProvider::class)
        ->args([service('delivery_time.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(ProductProvider::class)
        ->args([
            service('product.repository'),
            service('router'),
        ])
        ->tag('shopware.dataProvider.provider');

    $services->set(TaxProvider::class)
        ->args([service('tax.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(TaxRuleProvider::class)
        ->args([service('tax_rule.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(PropertyGroupProvider::class)
        ->args([service('property_group.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(UnitProvider::class)
        ->args([service('unit.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(RuleProvider::class)
        ->args([service('rule.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CountryProvider::class)
        ->args([service('country.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CountryStateProvider::class)
        ->args([service('country_state.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SalesChannelProvider::class)
        ->args([service('sales_channel.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SalesChannelDomainProvider::class)
        ->args([service('sales_channel_domain.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SalutationProvider::class)
        ->args([service('salutation.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(ShippingMethodProvider::class)
        ->args([service('shipping_method.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CustomerGroupProvider::class)
        ->args([service('customer_group.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CustomFieldSetProvider::class)
        ->args([service('custom_field_set.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(NumberRangeProvider::class)
        ->args([service('number_range.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SnippetProvider::class)
        ->args([service('snippet.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SnippetSetProvider::class)
        ->args([service('snippet_set.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(MailHeaderFooterProvider::class)
        ->args([service('mail_header_footer.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(MailTemplateProvider::class)
        ->args([service('mail_template.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(ProductFeatureSetProvider::class)
        ->args([service('product_feature_set.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(PaymentMethodProvider::class)
        ->args([service('payment_method.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(UserProvider::class)
        ->args([service('user.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(OrderProvider::class)
        ->args([service('order.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(MediaProvider::class)
        ->args([service('media.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SeoUrlTemplateProvider::class)
        ->args([service('seo_url_template.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SeoUrlProvider::class)
        ->args([service('seo_url.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(NewsletterRecipientProvider::class)
        ->args([service('newsletter_recipient.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CustomerProvider::class)
        ->args([service('customer.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CustomerWishlistProvider::class)
        ->args([service('customer_wishlist.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(SystemConfigProvider::class)
        ->args([service('system_config.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(PageSystemConfigProvider::class)
        ->args([service('system_config.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(ProductSortingProvider::class)
        ->args([service('product_sorting.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CmsPageProvider::class)
        ->args([service('cms_page.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(ProductStreamProvider::class)
        ->args([service('product_stream.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(ProductStreamFilterInheritanceProvider::class)
        ->args([service('product_stream_filter.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(CrossSellingProvider::class)
        ->args([service('product_cross_selling.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(ProductReviewProvider::class)
        ->args([service('product_review.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(DocumentProvider::class)
        ->args([
            service('document.repository'),
            service('router'),
        ])
        ->tag('shopware.dataProvider.provider');

    $services->set(DocumentInheritanceProvider::class)
        ->args([service('document.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(DocumentBaseConfigProvider::class)
        ->args([service('document_base_config.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(PromotionProvider::class)
        ->args([service('promotion.repository')])
        ->tag('shopware.dataProvider.provider');

    $services->set(EnvironmentService::class)
        ->args([
            service('currency.repository'),
            service('language.repository'),
            '%kernel.shopware_version%',
            '%kernel.shopware_version_revision%',
            service(StoreClient::class),
            service(AbstractExtensionDataProvider::class),
            service(SystemConfigService::class),
        ]);
};
