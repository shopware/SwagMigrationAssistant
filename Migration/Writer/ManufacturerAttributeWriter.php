<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class ManufacturerAttributeWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::PRODUCT_MANUFACTURER_CUSTOM_FIELD;
    }
}
