<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class SalesChannelWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::SALES_CHANNEL;
    }
}
