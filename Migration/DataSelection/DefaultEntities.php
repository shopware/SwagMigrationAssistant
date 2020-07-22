<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\DataSelection;

final class DefaultEntities
{
    public const CATEGORY = 'category';

    public const CATEGORY_TRANSLATION = 'category_translation';

    public const CATEGORY_CUSTOM_FIELD = 'category_custom_field';

    public const COUNTRY = 'country';

    public const COUNTRY_TRANSLATION = 'country_translation';

    public const COUNTRY_STATE = 'country_state';

    public const COUNTRY_STATE_TRANSLATION = 'country_state_translation';

    public const CURRENCY = 'currency';

    public const CURRENCY_TRANSLATION = 'currency_translation';

    public const CUSTOM_FIELD_SET = 'custom_field_set';

    public const CUSTOM_FIELD_SET_RELATION = 'custom_field_set_relation';

    public const CUSTOMER = 'customer';

    public const CUSTOMER_CUSTOM_FIELD = 'customer_custom_field';

    public const CUSTOMER_ADDRESS = 'customer_address';

    public const CUSTOMER_GROUP = 'customer_group';

    public const CUSTOMER_GROUP_TRANSLATION = 'customer_group_translation';

    public const CUSTOMER_GROUP_CUSTOM_FIELD = 'customer_group_custom_field';

    public const CROSS_SELLING = 'product_cross_selling';

    public const CROSS_SELLING_ACCESSORY = 'cross_selling_accessory_item';

    public const CROSS_SELLING_SIMILAR = 'cross_selling_similar_item';

    public const DELIVERY_TIME = 'delivery_time';

    public const LANGUAGE = 'language';

    public const LOCALE = 'locale';

    public const MAIL_TEMPLATE = 'mail_template';

    public const MEDIA = 'media';

    public const MEDIA_DEFAULT_FOLDER = 'media_default_folder';

    public const MEDIA_FOLDER = 'media_folder';

    public const MEDIA_FOLDER_CONFIGURATION = 'media_folder_configuration';

    public const MEDIA_THUMBNAIL_SIZE = 'media_thumbnail_size';

    public const MEDIA_TRANSLATION = 'media_translation';

    public const NEWSLETTER_RECIPIENT = 'newsletter_recipient';

    public const NUMBER_RANGE = 'number_range';

    public const NUMBER_RANGE_SALES_CHANNEL = 'number_range_sales_channel';

    public const NUMBER_RANGE_TRANSLATION = 'number_range_translation';

    public const ORDER = 'order';

    public const ORDER_ADDRESS = 'order_address';

    public const ORDER_DELIVERY = 'order_delivery';

    public const ORDER_DELIVERY_POSITION = 'order_delivery_position';

    public const ORDER_DOCUMENT = 'order_document';

    public const ORDER_DOCUMENT_CUSTOM_FIELD = 'order_document_custom_field';

    public const ORDER_DOCUMENT_MEDIA = 'order_document_media';

    public const ORDER_DOCUMENT_TYPE = 'order_document_type';

    public const ORDER_LINE_ITEM = 'order_line_item';

    public const ORDER_TRANSACTION = 'order_transaction';

    public const ORDER_CUSTOM_FIELD = 'order_custom_field';

    public const PAYMENT_METHOD = 'payment_method';

    public const PRODUCT = 'product';

    public const PRODUCT_CONTAINER = 'product_container';

    public const PRODUCT_CUSTOM_FIELD = 'product_custom_field';

    public const PRODUCT_MAIN = 'product_mainProduct';

    public const PRODUCT_MANUFACTURER = 'product_manufacturer';

    public const PRODUCT_MANUFACTURER_TRANSLATION = 'product_manufacturer_translation';

    public const PRODUCT_MANUFACTURER_CUSTOM_FIELD = 'product_manufacturer_custom_field';

    public const PRODUCT_MEDIA = 'product_media';

    public const PRODUCT_PRICE = 'product_price';

    public const PRODUCT_PRICE_CUSTOM_FIELD = 'product_price_custom_field';

    public const PRODUCT_TRANSLATION = 'product_translation';

    public const PRODUCT_OPTION_RELATION = 'product_option_relation';

    public const PRODUCT_PROPERTY_RELATION = 'product_property_relation';

    public const PRODUCT_PROPERTY = 'product_property';

    public const PRODUCT_REVIEW = 'product_review';

    public const PRODUCT_VISIBILITY = 'product_visibility';

    public const PROPERTY_GROUP = 'property_group';

    public const PROPERTY_GROUP_TRANSLATION = 'property_group_translation';

    public const PROPERTY_GROUP_TYPE_OPTION = 'property_group_option';

    public const PROPERTY_GROUP_TYPE_PROPERTY = 'property_group_property';

    public const PROPERTY_GROUP_OPTION = 'property_group_option';

    public const PROPERTY_GROUP_OPTION_TRANSLATION = 'property_group_option_translation';

    public const PROPERTY_GROUP_OPTION_TYPE_OPTION = 'property_group_option_option';

    public const PROPERTY_GROUP_OPTION_TYPE_PROPERTY = 'property_group_option_property';

    public const RULE = 'rule';

    public const SALUTATION = 'salutation';

    public const SALES_CHANNEL = 'sales_channel';

    public const SALES_CHANNEL_TRANSLATION = 'sales_channel_translation';

    public const SEO_URL = 'seo_url';

    public const SHIPPING_METHOD = 'shipping_method';

    public const SHIPPING_METHOD_PRICE = 'shipping_method_price';

    public const SHIPPING_METHOD_TRANSLATION = 'shipping_method_translation';

    public const TAX = 'tax';

    public const TRANSLATION = 'translation';

    public const UNIT = 'unit';

    public const UNIT_TRANSLATION = 'unit_translation';
}
