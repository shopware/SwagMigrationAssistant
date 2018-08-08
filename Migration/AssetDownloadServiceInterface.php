<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use Shopware\Core\Framework\Context;

interface AssetDownloadServiceInterface
{
    /**
     * Downloads all assets out of the migration mapping table
     */
    public function downloadAssets(Context $context): void;
}
