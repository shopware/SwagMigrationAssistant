<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class MediaFolderWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::MEDIA_FOLDER;
    }
}
