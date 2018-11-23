<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;

interface CliAssetDownloadServiceInterface
{
    /**
     * Downloads all assets out of the migration mapping table
     */
    public function downloadAssets(string $profile, Context $context): void;

    public function setLogger(LoggerInterface $logger): void;
}
