<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Mock\Migration\Media;

use GuzzleHttp\Client;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Media\CliMediaDownloadService;

class DummyCliMediaDownloadService extends CliMediaDownloadService
{
    protected function normalDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
    }

    protected function chunkDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
    }
}
