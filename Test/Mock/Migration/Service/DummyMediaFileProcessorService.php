<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorService;

class DummyMediaFileProcessorService extends MediaFileProcessorService
{
    public function processMediaFiles(
        MigrationContextInterface $migrationContext,
        Context $context,
        int $fileChunkByteSize
    ): void {

    }
}
