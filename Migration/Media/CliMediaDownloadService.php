<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Media;

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
use SwagMigrationNext\Command\Event\MigrationMediaDownloadAdvanceEvent;
use SwagMigrationNext\Command\Event\MigrationMediaDownloadFinishEvent;
use SwagMigrationNext\Command\Event\MigrationMediaDownloadStartEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CliMediaDownloadService implements CliMediaDownloadServiceInterface
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
    private $skippedMediaCount = 0;

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

    public function downloadMedia(string $runId, Context $context): void
    {
        $client = new Client();

        $mediaCount = 0;
        $this->dispatchStartEvent($runId, $context);
        $media = $this->fetchMediaFiles($runId, $context, 10);
        while (\count($media) > 0) {
            $mediaCount += \count($media);
            $mediaUuids = [];

            /** @var SwagMigrationMediaFileEntity $mediaFile */
            foreach ($media as $mediaFile) {
                /** @var string $uuid */
                $uuid = $mediaFile->getMediaId();
                $uri = $mediaFile->getUri();
                $fileSize = $mediaFile->getFileSize();
                $mediaUuids[] = $uuid;

                if ($uri !== null) {
                    $this->event->dispatch(MigrationMediaDownloadAdvanceEvent::EVENT_NAME, new MigrationMediaDownloadAdvanceEvent());
                    $this->download($client, $uuid, $uri, $fileSize, $context);
                }
            }
            $this->setProcessedFlag($runId, $context, $mediaUuids);

            $media = $this->fetchMediaFiles($runId, $context, 10);
        }
        $this->event->dispatch(MigrationMediaDownloadFinishEvent::EVENT_NAME, new MigrationMediaDownloadFinishEvent($mediaCount, $this->skippedMediaCount));
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
        $criteria->addFilter(new EqualsFilter('processed', false));
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
        $criteria->addFilter(new EqualsFilter('processed', false));
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));
        $migrationData = $this->mediaFileRepo->search($criteria, $context);

        $this->event->dispatch(MigrationMediaDownloadStartEvent::EVENT_NAME, new MigrationMediaDownloadStartEvent($migrationData->getTotal()));
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
            ++$this->skippedMediaCount;
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

    private function setProcessedFlag(string $runId, Context $context, array $finishedUuids): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $finishedUuids));
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        $updateProcessedMediaFiles = [];
        foreach ($mediaFiles->getElements() as $data) {
            /* @var SwagMigrationMediaFileEntity $data */
            $updateProcessedMediaFiles[] = [
                'id' => $data->getId(),
                'processed' => true,
            ];
        }

        if (empty($updateProcessedMediaFiles)) {
            return;
        }

        $this->mediaFileRepo->update($updateProcessedMediaFiles, $context);
    }
}
