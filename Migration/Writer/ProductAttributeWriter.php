<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class ProductAttributeWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::PRODUCT_CUSTOM_FIELD;
    }
}
