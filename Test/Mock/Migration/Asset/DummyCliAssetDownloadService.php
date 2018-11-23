<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Asset;

use GuzzleHttp\Client;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Asset\CliAssetDownloadService;

class DummyCliAssetDownloadService extends CliAssetDownloadService
{
    protected function normalDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
    }

    protected function chunkDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
    }
}
