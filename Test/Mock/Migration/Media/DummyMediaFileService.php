<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Media;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Media\MediaFileService;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class DummyMediaFileService extends MediaFileService
{
    public function __construct()
    {
    }

    public function writeMediaFile(Context $context): void
    {
    }

    public function setWrittenFlag(array $converted, MigrationContextInterface $migrationContext, Context $context): void
    {
    }
}
