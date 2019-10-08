<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class OrderAttributeConverter extends AttributeConverter
{
    protected function getCustomFieldEntityName(): string
    {
        return DefaultEntities::ORDER;
    }
}
