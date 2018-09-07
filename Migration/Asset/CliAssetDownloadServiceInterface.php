<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Framework\Context;

interface CliAssetDownloadServiceInterface
{
    /**
     * Downloads all assets out of the migration mapping table
     */
    public function downloadAssets(string $profile, Context $context): void;
}
