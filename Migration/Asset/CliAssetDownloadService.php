<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadAdvanceEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadFinishEvent;
use SwagMigrationNext\Command\Event\MigrationAssetDownloadStartEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CliAssetDownloadService implements CliAssetDownloadServiceInterface
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
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var int
     */
    private $chunkSizeBytes = 1 * 1024 * 1024;

    /**
     * @var FileSaver
     */
    private $fileSaver;

    /**
     * @var EventDispatcherInterface
     */
    private $event;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $skippedAssetCount = 0;

    public function __construct(
        EntityRepositoryInterface $mediaFileRepo,
        FileSaver $fileSaver,
        EventDispatcherInterface $event,
        LoggerInterface $logger
    ) {
        $this->mediaFileRepo = $mediaFileRepo;
        $this->fileSaver = $fileSaver;
        $this->event = $event;
        $this->logger = $logger;
    }

    public function downloadAssets(string $runId, Context $context): void
    {
        $client = new Client();

        $assetCount = 0;
        $this->dispatchStartEvent($runId, $context);
        $assets = $this->fetchMediaFiles($runId, $context, 10);
        while (\count($assets) > 0) {
            $assetCount += \count($assets);
            $mediaUuids = [];

            /** @var SwagMigrationMediaFileEntity $asset */
            foreach ($assets as $asset) {
                /** @var string $uuid */
                $uuid = $asset->getMediaId();
                $uri = $asset->getUri();
                $fileSize = $asset->getFileSize();
                $mediaUuids[] = $uuid;

                if ($uri !== null) {
                    $this->event->dispatch(MigrationAssetDownloadAdvanceEvent::EVENT_NAME, new MigrationAssetDownloadAdvanceEvent());
                    $this->download($client, $uuid, $uri, $fileSize, $context);
                }
            }
            $this->setDownloadedFlag($runId, $context, $mediaUuids);

            $assets = $this->fetchMediaFiles($runId, $context, 10);
        }
        $this->event->dispatch(MigrationAssetDownloadFinishEvent::EVENT_NAME, new MigrationAssetDownloadFinishEvent($assetCount, $this->skippedAssetCount));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws MediaNotFoundException
     */
    protected function chunkDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
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

        $this->persistFileToMedia($filePath, $fileExtension, $uuid, $fileSize, $context);
    }

    /**
     * @throws MediaNotFoundException
     */
    protected function normalDownload(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
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

        $fileHandle = fopen($filePath, 'ab');
        fwrite($fileHandle, $response->getBody()->getContents());
        fclose($fileHandle);

        $this->persistFileToMedia($filePath, $fileExtension, $uuid, $fileSize, $context);
    }

    /**
     * @return SwagMigrationMediaFileEntity[]
     */
    private function fetchMediaFiles(string $runId, Context $context, int $limit): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $criteria->addFilter(new EqualsFilter('written', true));
        $criteria->addFilter(new EqualsFilter('downloaded', false));
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));
        $migrationData = $this->mediaFileRepo->search($criteria, $context);

        if ($migrationData->getTotal() === 0) {
            return [];
        }

        return $migrationData->getElements();
    }

    private function dispatchStartEvent(string $runId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $criteria->addFilter(new EqualsFilter('written', true));
        $criteria->addFilter(new EqualsFilter('downloaded', false));
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));
        $migrationData = $this->mediaFileRepo->search($criteria, $context);

        $this->event->dispatch(MigrationAssetDownloadStartEvent::EVENT_NAME, new MigrationAssetDownloadStartEvent($migrationData->getTotal()));
    }

    private function download(Client $client, string $uuid, string $uri, int $fileSize, Context $context): void
    {
        if (!is_dir('_temp') && !mkdir('_temp') && !is_dir('_temp')) {
            return;
        }

        try {
            if ($fileSize > self::MAX_FILESIZE) {
                $this->chunkDownload($client, $uuid, $uri, $fileSize, $context);
            } else {
                $this->normalDownload($client, $uuid, $uri, $fileSize, $context);
            }
        } catch (GuzzleException $exception) {
            ++$this->skippedAssetCount;
            $this->logger->error('HTTP-Error: ' . $exception->getMessage(), ['uri' => $uri, 'uuid' => $uuid]);

            return;
        }
    }

    /**
     * @throws MediaNotFoundException
     */
    private function persistFileToMedia(string $filePath, string $fileExtension, string $uuid, int $fileSize, Context $context): void
    {
        $mimeType = mime_content_type($filePath);
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        $this->fileSaver->persistFileToMedia($mediaFile, $uuid, $uuid, $context);
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

    private function setDownloadedFlag(string $runId, Context $context, array $finishedUuids): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $finishedUuids));
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        $updateDownloadedMediaFiles = [];
        foreach ($mediaFiles->getElements() as $data) {
            /* @var SwagMigrationMediaFileEntity $data */
            $updateDownloadedMediaFiles[] = [
                'id' => $data->getId(),
                'downloaded' => true,
            ];
        }

        if (empty($updateDownloadedMediaFiles)) {
            return;
        }

        $this->mediaFileRepo->update($updateDownloadedMediaFiles, $context);
    }
}
