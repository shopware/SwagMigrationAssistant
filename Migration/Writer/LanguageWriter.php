<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class LanguageWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::LANGUAGE;
    }
}
