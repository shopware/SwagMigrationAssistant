<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Service\MediaFileProcessorService;

class DummyMediaFileProcessorService extends MediaFileProcessorService
{
    public function fetchMediaUuids(string $runUuid, Context $context, int $limit): array
    {
        return [
            0 => Uuid::uuid4()->getHex(),
            1 => Uuid::uuid4()->getHex(),
            2 => Uuid::uuid4()->getHex(),
            3 => Uuid::uuid4()->getHex(),
            4 => Uuid::uuid4()->getHex(),
            5 => Uuid::uuid4()->getHex(),
            6 => Uuid::uuid4()->getHex(),
            7 => Uuid::uuid4()->getHex(),
            8 => Uuid::uuid4()->getHex(),
            9 => Uuid::uuid4()->getHex(),
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
