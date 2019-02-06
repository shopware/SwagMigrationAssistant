<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Media;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;

interface CliMediaDownloadServiceInterface
{
    /**
     * Downloads all media out of the migration mapping table
     */
    public function downloadMedia(string $profile, Context $context): void;

    public function setLogger(LoggerInterface $logger): void;
}
