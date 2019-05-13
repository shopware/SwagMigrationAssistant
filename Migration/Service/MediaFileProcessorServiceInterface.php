<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

interface MediaFileProcessorServiceInterface
{
    public function fetchMediaUuids(string $runUuid, Context $context, int $limit): array;

    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array;
}
