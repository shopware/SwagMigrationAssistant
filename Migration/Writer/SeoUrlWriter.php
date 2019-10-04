<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class SeoUrlWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::SEO_URL;
    }
}
