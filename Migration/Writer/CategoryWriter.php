<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class CategoryWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::CATEGORY;
    }
}
