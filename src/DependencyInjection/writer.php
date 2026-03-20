<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Shopware\Core\Content\Seo\SeoUrlPersister;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\NumberRange\NumberRangeDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use SwagMigrationAssistant\Migration\ErrorResolution\MigrationErrorResolutionService;
use SwagMigrationAssistant\Migration\Writer\AbstractWriter;
use SwagMigrationAssistant\Migration\Writer\CategoryAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\CategoryWriter;
use SwagMigrationAssistant\Migration\Writer\CountryWriter;
use SwagMigrationAssistant\Migration\Writer\CrossSellingWriter;
use SwagMigrationAssistant\Migration\Writer\CurrencyWriter;
use SwagMigrationAssistant\Migration\Writer\CustomerAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\CustomerGroupAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\CustomerGroupWriter;
use SwagMigrationAssistant\Migration\Writer\CustomerWishlistWriter;
use SwagMigrationAssistant\Migration\Writer\CustomerWriter;
use SwagMigrationAssistant\Migration\Writer\LanguageWriter;
use SwagMigrationAssistant\Migration\Writer\MainVariantRelationWriter;
use SwagMigrationAssistant\Migration\Writer\ManufacturerAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\MediaFolderWriter;
use SwagMigrationAssistant\Migration\Writer\MediaWriter;
use SwagMigrationAssistant\Migration\Writer\NewsletterRecipientWriter;
use SwagMigrationAssistant\Migration\Writer\NumberRangeWriter;
use SwagMigrationAssistant\Migration\Writer\OrderAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\OrderDocumentAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\OrderDocumentWriter;
use SwagMigrationAssistant\Migration\Writer\OrderWriter;
use SwagMigrationAssistant\Migration\Writer\ProductAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\ProductPriceAttributeWriter;
use SwagMigrationAssistant\Migration\Writer\ProductReviewWriter;
use SwagMigrationAssistant\Migration\Writer\ProductWriter;
use SwagMigrationAssistant\Migration\Writer\PropertyGroupOptionWriter;
use SwagMigrationAssistant\Migration\Writer\SalesChannelWriter;
use SwagMigrationAssistant\Migration\Writer\SeoUrlWriter;
use SwagMigrationAssistant\Migration\Writer\ShippingMethodWriter;
use SwagMigrationAssistant\Migration\Writer\TranslationWriter;
use SwagMigrationAssistant\Migration\Writer\WriterRegistry;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(WriterRegistry::class, WriterRegistry::class)
        ->args([tagged_iterator('shopware.migration.writer')]);

    $services->set(AbstractWriter::class)
        ->abstract();

    $services->set(ProductWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(TranslationWriter::class)
        ->args([
            service(EntityWriter::class),
            service(DefinitionInstanceRegistry::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CategoryWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CategoryDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(MediaFolderWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(MediaFolderDefinition::class),
            service('media_folder.repository'),
        ])
        ->tag('shopware.migration.writer');

    $services->set(MediaWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(MediaDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(NumberRangeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(NumberRangeDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(LanguageWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(LanguageDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CustomerAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CustomerWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomerDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(OrderAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(OrderWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(OrderDefinition::class),
            service(StructNormalizer::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(OrderDocumentAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(OrderDocumentWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(DocumentDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CustomerGroupAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CustomerGroupWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomerGroupDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CustomerWishlistWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomerWishlistDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ManufacturerAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductPriceAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CategoryAttributeWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CustomFieldSetDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(PropertyGroupOptionWriter::class)
        ->args([
            service(EntityWriter::class),
            service(PropertyGroupOptionDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CurrencyWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CurrencyDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SalesChannelWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SalesChannelDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(NewsletterRecipientWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(NewsletterRecipientDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ShippingMethodWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ShippingMethodDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(ProductReviewWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductReviewDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(SeoUrlWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(SeoUrlDefinition::class),
            service('sales_channel.repository'),
            service(SeoUrlPersister::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CrossSellingWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductCrossSellingDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(MainVariantRelationWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(ProductDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(CountryWriter::class)
        ->parent(AbstractWriter::class)
        ->args([
            service(EntityWriter::class),
            service(CountryDefinition::class),
        ])
        ->tag('shopware.migration.writer');

    $services->set(MigrationErrorResolutionService::class)
        ->args([
            service(Connection::class),
            service('event_dispatcher'),
        ]);
};
