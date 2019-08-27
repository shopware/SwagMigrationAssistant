<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class ProductPriceAttributeWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::PRODUCT_PRICE_CUSTOM_FIELD;
    }
}
