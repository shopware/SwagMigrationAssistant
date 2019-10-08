<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class ManufacturerAttributeConverter extends AttributeConverter
{
    protected function getCustomFieldEntityName(): string
    {
        return DefaultEntities::PRODUCT_MANUFACTURER;
    }
}
