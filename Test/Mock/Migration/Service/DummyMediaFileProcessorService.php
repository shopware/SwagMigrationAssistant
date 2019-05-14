<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Test\Mock\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Service\MediaFileProcessorService;

class DummyMediaFileProcessorService extends MediaFileProcessorService
{
    public function fetchMediaUuids(string $runUuid, Context $context, int $limit): array
    {
        return [
            0 => Uuid::randomHex(),
            1 => Uuid::randomHex(),
            2 => Uuid::randomHex(),
            3 => Uuid::randomHex(),
            4 => Uuid::randomHex(),
            5 => Uuid::randomHex(),
            6 => Uuid::randomHex(),
            7 => Uuid::randomHex(),
            8 => Uuid::randomHex(),
            9 => Uuid::randomHex(),
        ];
    }

    public function processMediaFiles(
        MigrationContextInterface $migrationContext,
        Context $context,
        array $workload,
        int $fileChunkByteSize
    ): array {
        return $workload;
    }
}
