<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Exception\IllegalMimeTypeException;
use Shopware\Core\Content\Media\Upload\MediaUpdater;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\Struct\ArrayStruct;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadAdvanceEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadFinishEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadStartEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AssetDownloadService implements AssetDownloadServiceInterface
{
    /**
     * @var int
     */
    private const MAX_FILESIZE = 1024 * 1024;

    /**
     * @var float
     */
    private const MAX_REQUEST_TIME = 4.0;

    /**
     * @var int
     */
    private const CHUNK_INCREMENT = 256 * 1024;

    /**
     * @var RepositoryInterface
     */
    private $migrationMappingRepository;

    /**
     * @var int
     */
    private $chunkSizeBytes = 1 * 1024 * 1024;

    /**
     * @var MediaUpdater
     */
    private $mediaUpdater;

    /**
     * @var EventDispatcherInterface
     */
    private $event;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(RepositoryInterface $migrationMappingRepository, MediaUpdater $mediaUpdater, EventDispatcherInterface $event, LoggerInterface $logger)
    {
        $this->migrationMappingRepository = $migrationMappingRepository;
        $this->mediaUpdater = $mediaUpdater;
        $this->event = $event;
        $this->logger = $logger;
    }

    public function downloadAssets(Context $context): void
    {
        $client = new Client();
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('entity', 'media'));

        /**
         * @var ArrayStruct[]
         */
        $entitySearchResult = $this->migrationMappingRepository->search($criteria, $context);
        $assets = $entitySearchResult->getElements();

        $this->event->dispatch(MigrationAssetDownloadStartEvent::EVENT_NAME, new MigrationAssetDownloadStartEvent($entitySearchResult->getTotal()));
        foreach ($assets as $asset) {
            $uuid = $asset->get('entityUuid');
            $additionalData = $asset->get('additionalData');

            if (is_array($additionalData) && isset($additionalData['uri'])) {
                $this->event->dispatch(MigrationAssetDownloadAdvanceEvent::EVENT_NAME, new MigrationAssetDownloadAdvanceEvent($additionalData['uri']));
                $this->download($client, $uuid, $additionalData['uri'], (int) $additionalData['file_size'], $context);
            }
        }
        $this->event->dispatch(MigrationAssetDownloadFinishEvent::EVENT_NAME, new MigrationAssetDownloadFinishEvent($entitySearchResult->getTotal()));
    }

    private function download(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
        if (!is_dir('_temp')) {
            if (!mkdir('_temp')) {
                return;
            }
        }

        try {
            if ($fileSize > self::MAX_FILESIZE) {
                $this->chunckDownload($client, $uuid, $uri, $fileSize, $context);
            } else {
                $this->normalDownload($client, $uuid, $uri, $fileSize, $context);
            }
        } catch (GuzzleException $exception) {
            $this->logger->error("HTTP-Error: " . $exception->getMessage(), ['uri' => $uri, 'uuid' => $uuid]);
            return;
        }
    }

    /**
     * @throws GuzzleException
     * @throws IllegalMimeTypeException
     */
    private function chunckDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
        $fileExtension = pathinfo($uri, PATHINFO_EXTENSION);
        $filePath = sprintf('_temp/%s.%s', $uuid, $fileExtension);

        $chunkStart = 0;

        $fileHandle = fopen($filePath, 'wb');
        while ($chunkStart < $fileSize) {
            $chunkEnd = $chunkStart + $this->chunkSizeBytes;

            $startTime = microtime(true);
            /** @var GuzzleResponse $result */
            $response = $client->get(
                $uri,
                [
                    'query' => ['alt' => 'media'],
                    'headers' => [
                        'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd),
                    ],
                ]
            );
            $chunkStart = $chunkEnd + 1;
            $requestTime = microtime(true) - $startTime;
            $this->handleChunkSize($requestTime);

            fwrite($fileHandle, $response->getBody()->getContents());
        }
        fclose($fileHandle);

        $this->persistFileToMedia($filePath, $uuid, $fileSize, $context);
    }

    /**
     * @throws GuzzleException
     * @throws IllegalMimeTypeException
     */
    private function normalDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
        $fileExtension = pathinfo($uri, PATHINFO_EXTENSION);
        $filePath = sprintf('_temp/%s.%s', $uuid, $fileExtension);

        $response = $client->request(
            'GET',
            $uri,
            [
                'query' => ['alt' => 'media'],
            ]
        );

        $fileHandle = fopen($filePath, 'wb');
        fwrite($fileHandle, $response->getBody()->getContents());
        fclose($fileHandle);

        $this->persistFileToMedia($filePath, $uuid, $fileSize, $context);
    }

    /**
     * @throws IllegalMimeTypeException
     */
    private function persistFileToMedia(string $filePath, string $uuid, int $fileSize, Context $context): void
    {
        $mimeType = mime_content_type($filePath);
        $context->getExtension('write_protection')->set('write_media', true);
        $this->mediaUpdater->persistFileToMedia($filePath, $uuid, $mimeType, $fileSize, $context);
    }

    private function handleChunkSize(float $requestTime): void
    {
        if ($requestTime < self::MAX_REQUEST_TIME) {
            $this->chunkSizeBytes += self::CHUNK_INCREMENT;
        }

        if ($requestTime > self::MAX_REQUEST_TIME && $this->chunkSizeBytes > self::CHUNK_INCREMENT) {
            $this->chunkSizeBytes -= self::CHUNK_INCREMENT;
        }
    }
}
