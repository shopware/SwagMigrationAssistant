<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\Context;

interface HttpAssetDownloadServiceInterface
{
    public function fetchMediaUuids(string $runId, Context $context, int $limit): array;

    public function downloadAssets(string $runId, Context $context, array $workload, int $fileChunkByteSize): array;
}
