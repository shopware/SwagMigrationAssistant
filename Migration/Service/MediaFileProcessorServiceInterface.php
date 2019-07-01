<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MediaFileProcessorServiceInterface
{
    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context, int $fileChunkByteSize): void;
}
