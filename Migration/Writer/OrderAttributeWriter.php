<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class OrderAttributeWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::ORDER_CUSTOM_FIELD;
    }
}
