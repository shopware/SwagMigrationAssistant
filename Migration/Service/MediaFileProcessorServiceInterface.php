<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\MigrationContextInterface;

interface MediaFileProcessorServiceInterface
{
    public function fetchMediaUuids(string $runUuid, Context $context, int $limit): array;

    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array;
}
