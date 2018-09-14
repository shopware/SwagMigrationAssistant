<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\Context;

interface HttpAssetDownloadServiceInterface
{
    public function fetchMediaUuids(Context $context, string $profile, int $offset, int $limit): array;

    public function downloadAssets(Context $context, array $workload, int $fileChunkByteSize): array;
}
