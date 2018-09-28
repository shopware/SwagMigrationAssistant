<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\Context;

interface HttpAssetDownloadServiceInterface
{
    public function fetchMediaUuids(Context $context, string $runId, int $limit): array;

    public function downloadAssets(string $runId, Context $context, array $workload, int $fileChunkByteSize): array;
}
