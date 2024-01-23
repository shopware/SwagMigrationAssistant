<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

use Shopware\Core\Framework\Log\Package;

#[Package('services-settings')]
final class DefaultEntities
{
    final public const CATEGORY = 'category';

    final public const CATEGORY_ASSOCIATION = 'category_association';

    final public const CATEGORY_CMS_PAGE_ASSOCIATION = 'category_cms_page_association';

    final public const CATEGORY_PRODUCT_STREAM_ASSOCIATION = 'category_product_stream_association';

    final public const CATEGORY_TRANSLATION = 'category_translation';

    final public const CATEGORY_CUSTOM_FIELD = 'category_custom_field';

    final public const CMS_PAGE = 'cms_page';

    final public const COUNTRY = 'country';

    final public const COUNTRY_TRANSLATION = 'country_translation';

    final public const COUNTRY_STATE = 'country_state';

    final public const COUNTRY_STATE_TRANSLATION = 'country_state_translation';

    final public const CURRENCY = 'currency';

    final public const CURRENCY_TRANSLATION = 'currency_translation';

    final public const CUSTOM_FIELD_SET = 'custom_field_set';

    final public const CUSTOM_FIELD_SET_RELATION = 'custom_field_set_relation';

    final public const CUSTOMER = 'customer';

    final public const CUSTOMER_CUSTOM_FIELD = 'customer_custom_field';

    final public const CUSTOMER_ADDRESS = 'customer_address';

    final public const CUSTOMER_GROUP = 'customer_group';

    final public const CUSTOMER_GROUP_TRANSLATION = 'customer_group_translation';

    final public const CUSTOMER_GROUP_CUSTOM_FIELD = 'customer_group_custom_field';

    final public const CROSS_SELLING = 'product_cross_selling';

    final public const CROSS_SELLING_ACCESSORY = 'product_cross_selling_accessory';

    final public const CROSS_SELLING_SIMILAR = 'product_cross_selling_similar';

    final public const CUSTOMER_WISHLIST = 'customer_wishlist';

    final public const DELIVERY_TIME = 'delivery_time';

    final public const LANGUAGE = 'language';

    final public const LOCALE = 'locale';

    final public const MAIL_TEMPLATE = 'mail_template';

    final public const MAIL_TEMPLATE_TYPE = 'mail_template_type';

    final public const MEDIA = 'media';

    final public const MEDIA_DEFAULT_FOLDER = 'media_default_folder';

    final public const MEDIA_FOLDER = 'media_folder';

    final public const MEDIA_FOLDER_INHERITANCE = 'media_folder_inheritance'; // simulated entity for parent relationships

    final public const MEDIA_FOLDER_CONFIGURATION = 'media_folder_configuration';

    final public const MEDIA_THUMBNAIL_SIZE = 'media_thumbnail_size';

    final public const MEDIA_TRANSLATION = 'media_translation';

    final public const MAIN_VARIANT_RELATION = 'main_variant_relation';

    final public const NEWSLETTER_RECIPIENT = 'newsletter_recipient';

    final public const NUMBER_RANGE = 'number_range';

    final public const NUMBER_RANGE_SALES_CHANNEL = 'number_range_sales_channel';

    final public const NUMBER_RANGE_TRANSLATION = 'number_range_translation';

    final public const NUMBER_RANGE_TYPE = 'number_range_type';

    final public const ORDER = 'order';

    final public const ORDER_CUSTOMER = 'order_customer';
    final public const ORDER_ADDRESS = 'order_address';

    final public const ORDER_DELIVERY = 'order_delivery';

    final public const ORDER_DELIVERY_POSITION = 'order_delivery_position';

    final public const ORDER_DOCUMENT = 'order_document';

    final public const ORDER_DOCUMENT_GENERATED = 'order_document_generated';

    final public const ORDER_DOCUMENT_GENERATED_MEDIA = 'order_document_generated_media';

    final public const ORDER_DOCUMENT_GENERATED_MEDIA_FILE = 'order_document_generated_media_file';

    final public const ORDER_DOCUMENT_BASE_CONFIG = 'order_document_base_config';

    final public const ORDER_DOCUMENT_CUSTOM_FIELD = 'order_document_custom_field';

    final public const ORDER_DOCUMENT_INHERITANCE = 'order_document_inheritance';

    final public const ORDER_DOCUMENT_MEDIA = 'order_document_media';

    final public const ORDER_DOCUMENT_TYPE = 'order_document_type';

    final public const ORDER_LINE_ITEM = 'order_line_item';

    final public const ORDER_LINE_ITEM_DOWNLOAD = 'order_line_item_download';

    final public const ORDER_TRANSACTION = 'order_transaction';

    final public const ORDER_CUSTOM_FIELD = 'order_custom_field';

    final public const PAGE_SYSTEM_CONFIG = 'page_system_config';

    final public const PAYMENT_METHOD = 'payment_method';

    final public const PRODUCT = 'product';

    final public const PRODUCT_CONTAINER = 'product_container';

    final public const PRODUCT_CUSTOM_FIELD = 'product_custom_field';

    final public const PRODUCT_FEATURE_SET = 'product_feature_set';

    final public const PRODUCT_MAIN = 'product_mainProduct';

    final public const PRODUCT_MANUFACTURER = 'product_manufacturer';

    final public const PRODUCT_MANUFACTURER_TRANSLATION = 'product_manufacturer_translation';

    final public const PRODUCT_MANUFACTURER_CUSTOM_FIELD = 'product_manufacturer_custom_field';

    final public const PRODUCT_MEDIA = 'product_media';

    final public const PRODUCT_DOWNLOAD = 'product_download';

    final public const PRODUCT_PRICE = 'product_price';

    final public const PRODUCT_PRICE_CUSTOM_FIELD = 'product_price_custom_field';

    final public const PRODUCT_TRANSLATION = 'product_translation';

    final public const PRODUCT_OPTION_RELATION = 'product_option_relation';

    final public const PRODUCT_PROPERTY_RELATION = 'product_property_relation';

    final public const PRODUCT_PROPERTY = 'product_property';

    final public const PRODUCT_REVIEW = 'product_review';

    final public const PRODUCT_SORTING = 'product_sorting';

    final public const PRODUCT_STREAM = 'product_stream';

    final public const PRODUCT_STREAM_FILTER_INHERITANCE = 'product_stream_filter_inheritance';

    final public const PRODUCT_VISIBILITY = 'product_visibility';

    final public const PROMOTION = 'promotion';

    final public const PROMOTION_DISCOUNT = 'promotion_discount';

    final public const PROMOTION_DISCOUNT_RULE = 'promotion_discount_rule';

    final public const PROMOTION_INDIVIDUAL_CODE = 'promotion_individual_code';

    final public const PROMOTION_PERSONA_RULE = 'promotion_persona_rule';

    final public const PROPERTY_GROUP = 'property_group';

    final public const PROPERTY_GROUP_TRANSLATION = 'property_group_translation';

    final public const PROPERTY_GROUP_TYPE_OPTION = 'property_group_option';

    final public const PROPERTY_GROUP_TYPE_PROPERTY = 'property_group_property';

    final public const PROPERTY_GROUP_OPTION = 'property_group_option';

    final public const PROPERTY_GROUP_OPTION_TRANSLATION = 'property_group_option_translation';

    final public const PROPERTY_GROUP_OPTION_TYPE_OPTION = 'property_group_option_option';

    final public const PROPERTY_GROUP_OPTION_TYPE_PROPERTY = 'property_group_option_property';

    final public const RULE = 'rule';

    final public const SALUTATION = 'salutation';

    final public const SALES_CHANNEL = 'sales_channel';

    final public const SALES_CHANNEL_DOMAIN = 'sales_channel_domain';

    final public const SALES_CHANNEL_TRANSLATION = 'sales_channel_translation';

    final public const SEO_URL = 'seo_url';

    final public const SEO_URL_TEMPLATE = 'seo_url_template';

    final public const SHIPPING_METHOD = 'shipping_method';

    final public const SHIPPING_METHOD_PRICE = 'shipping_method_price';

    final public const SHIPPING_METHOD_TRANSLATION = 'shipping_method_translation';

    final public const SNIPPET = 'snippet';

    final public const SNIPPET_SET = 'snippet_set';

    final public const STATE_MACHINE_STATE = 'state_machine_state';

    final public const SYSTEM_CONFIG = 'system_config';

    final public const TAX = 'tax';

    final public const TAX_RULE = 'tax_rule';

    final public const TAX_RULE_TYPE = 'tax_rule_type';

    final public const TRANSLATION = 'translation';

    final public const UNIT = 'unit';

    final public const UNIT_TRANSLATION = 'unit_translation';
}
