<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Media;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\NoFileSystemPermissionsException;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\AbstractMediaFileProcessor;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationAssistant\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class HttpMediaDownloadService extends AbstractMediaFileProcessor
{
    /**
     * @var FileSaver
     */
    private $fileSaver;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    public function __construct(
        EntityRepositoryInterface $migrationMediaFileRepo,
        FileSaver $fileSaver,
        LoggingServiceInterface $loggingService
    ) {
        $this->mediaFileRepo = $migrationMediaFileRepo;
        $this->fileSaver = $fileSaver;
        $this->loggingService = $loggingService;
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function getSupportedGatewayIdentifier(): string
    {
        return Shopware55ApiGateway::GATEWAY_NAME;
    }

    /**
     * @param array $workload [{ "uuid": "04ed51ccbb2341bc9b352d78e64213fb", "currentOffset": 0, "state": "inProgress" }]
     *
     * @throws MediaNotFoundException
     * @throws InconsistentCriteriaIdsException
     */
    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array
    {
        //Map workload with uuids as keys
        $mappedWorkload = [];
        $mediaIds = [];
        $runId = $migrationContext->getRunUuid();

        foreach ($workload as $work) {
            $mappedWorkload[$work['uuid']] = $work;
            $mediaIds[] = $work['uuid'];
        }

        if (!is_dir('_temp') && !mkdir('_temp') && !is_dir('_temp')) {
            $exception = new NoFileSystemPermissionsException();
            $this->loggingService->addError($runId, (string) $exception->getCode(), '', $exception->getMessage());
            $this->loggingService->saveLogging($context);

            return $workload;
        }

        //Fetch media from database
        $client = new Client([
            'verify' => false,
        ]);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaIds));
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $mediaSearchResult = $this->mediaFileRepo->search($criteria, $context);
        /** @var SwagMigrationMediaFileEntity[] $media */
        $media = $mediaSearchResult->getElements();

        //Do download requests and store the promises
        $promises = $this->doMediaDownloadRequests($media, $fileChunkByteSize, $mappedWorkload, $client);

        // Wait for the requests to complete, even if some of them fail
        /** @var array $results */
        $results = Promise\settle($promises)->wait();

        //handle responses
        $failureUuids = [];
        $finishedUuids = [];
        foreach ($results as $uuid => $result) {
            $state = $result['state'];
            $additionalData = $mappedWorkload[$uuid]['additionalData'];

            $oldWorkloadSearchResult = array_filter(
                $workload,
                function ($work) use ($uuid) {
                    return $work['uuid'] === $uuid;
                }
            );
            $oldWorkload = array_pop($oldWorkloadSearchResult);

            if ($state !== 'fulfilled') {
                $mappedWorkload[$uuid] = $oldWorkload;
                $mappedWorkload[$uuid]['additionalData'] = $additionalData;

                if (isset($mappedWorkload[$uuid]['errorCount'])) {
                    ++$mappedWorkload[$uuid]['errorCount'];
                } else {
                    $mappedWorkload[$uuid]['errorCount'] = 1;
                }

                if ($mappedWorkload[$uuid]['errorCount'] > ProcessMediaHandler::MEDIA_ERROR_THRESHOLD) {
                    $failureUuids[] = $uuid;
                    $mappedWorkload[$uuid]['state'] = 'error';
                    $this->loggingService->addError(
                        $mappedWorkload[$uuid]['runId'],
                        Shopware55LogTypes::CANNOT_DOWNLOAD_MEDIA,
                        '',
                        'Cannot download media.',
                        [
                            'uri' => $mappedWorkload[$uuid]['additionalData']['uri'],
                        ]
                    );
                }

                continue;
            }

            $response = $result['value'];
            $fileExtension = pathinfo($additionalData['uri'], PATHINFO_EXTENSION);
            $filePath = sprintf('_temp/%s.%s', $uuid, $fileExtension);

            $fileHandle = fopen($filePath, 'ab');
            fwrite($fileHandle, $response->getBody()->getContents());
            fclose($fileHandle);

            if ($mappedWorkload[$uuid]['state'] === 'finished') {
                //move media to media system
                $filename = $this->getMediaName($media, $uuid);
                $this->persistFileToMedia($filePath, $uuid, $filename, (int) $additionalData['file_size'], $fileExtension, $context);
                unlink($filePath);
                $finishedUuids[] = $uuid;
            }

            if (isset($mappedWorkload[$uuid]['errorCount'], $oldWorkload['errorCount'])
                && $oldWorkload['errorCount'] === $mappedWorkload[$uuid]['errorCount']) {
                unset($mappedWorkload[$uuid]['errorCount']);
            }
        }

        $this->setProcessedFlag($runId, $context, $finishedUuids, $failureUuids);
        $this->loggingService->saveLogging($context);

        return array_values($mappedWorkload);
    }

    /**
     * @param SwagMigrationMediaFileEntity[] $media
     */
    private function getMediaName(array $media, string $mediaId): string
    {
        foreach ($media as $mediaFile) {
            if ($mediaFile->getMediaId() === $mediaId) {
                return $mediaFile->getFileName();
            }
        }

        return '';
    }

    /**
     * Start all the download requests for the media in parallel (async) and return the promise array.
     *
     * @param SwagMigrationMediaFileEntity[] $media
     */
    private function doMediaDownloadRequests(array $media, int $fileChunkByteSize, array &$mappedWorkload, Client $client): array
    {
        $promises = [];
        foreach ($media as $mediaFile) {
            $uuid = strtolower($mediaFile->getMediaId());
            $additionalData = [];
            $additionalData['file_size'] = $mediaFile->getFileSize();
            $additionalData['uri'] = $mediaFile->getUri();
            $mappedWorkload[$uuid]['additionalData'] = $additionalData;

            /* Todo: Implement Chunkdownload
            if ($additionalData['file_size'] <= $fileChunkByteSize) {
                $promise = $this->doNormalDownloadRequest($mappedWorkload[$uuid], $client);
            } else {
                $promise = $this->doChunkDownloadRequest($fileChunkByteSize, $mappedWorkload[$uuid], $client);
            }
            */

            $promise = $this->doNormalDownloadRequest($mappedWorkload[$uuid], $client);

            if ($promise !== null) {
                $promises[$uuid] = $promise;
            }
        }

        return $promises;
    }

    /**
     * @throws MediaNotFoundException
     */
    private function persistFileToMedia(string $filePath, string $uuid, string $name, int $fileSize, string $fileExtension, Context $context): void
    {
        $mimeType = mime_content_type($filePath);
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);

        try {
            $this->fileSaver->persistFileToMedia($mediaFile, $name, $uuid, $context);
        } catch (DuplicatedMediaFileNameException $e) {
            $this->fileSaver->persistFileToMedia($mediaFile, $name . substr(Uuid::randomHex(), 0, 5), $uuid, $context);
        }
    }

    private function doNormalDownloadRequest(array &$workload, Client $client): ?Promise\PromiseInterface
    {
        $additionalData = $workload['additionalData'];

        try {
            $promise = $client->getAsync(
                $additionalData['uri'],
                [
                    'query' => ['alt' => 'media'],
                ]
            );

            $workload['currentOffset'] = $additionalData['file_size'];
            $workload['state'] = 'finished';
        } catch (\Exception $exception) {
            $promise = null;
            if (isset($workload['errorCount'])) {
                ++$workload['errorCount'];
            } else {
                $workload['errorCount'] = 1;
            }
        }

        return $promise;
    }

    private function doChunkDownloadRequest(int $fileChunkByteSize, array &$workload, Client $client): ?Promise\PromiseInterface
    {
        $additionalData = $workload['additionalData'];
        $chunkStart = (int) $workload['currentOffset'];
        $chunkEnd = $chunkStart + $fileChunkByteSize;

        try {
            $promise = $client->getAsync(
                $additionalData['uri'],
                [
                    'query' => ['alt' => 'media'],
                    'headers' => [
                        'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd),
                    ],
                ]
            );

            //check if chunk is big enough to finish the download of that media
            if ($chunkEnd < $additionalData['file_size']) {
                $workload['state'] = 'inProgress';
                $workload['currentOffset'] = $chunkEnd + 1;
            } else {
                $workload['state'] = 'finished';
                $workload['currentOffset'] = $additionalData['file_size'];
            }
        } catch (\Exception $exception) {
            $promise = null;
            if (isset($workload['errorCount'])) {
                ++$workload['errorCount'];
            } else {
                $workload['errorCount'] = 1;
            }
        }

        return $promise;
    }

    private function setProcessedFlag(string $runId, Context $context, array $finishedUuids, array $failureUuids): void
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

        if (!empty($failureUuids)) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter('mediaId', $failureUuids));
            $criteria->addFilter(new EqualsFilter('runId', $runId));
            $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

            foreach ($mediaFiles->getElements() as $data) {
                /* @var SwagMigrationMediaFileEntity $data */
                $updateProcessedMediaFiles[] = [
                    'id' => $data->getId(),
                    'processFailure' => true,
                ];
            }
        }

        if (empty($updateProcessedMediaFiles)) {
            return;
        }

        $this->mediaFileRepo->update($updateProcessedMediaFiles, $context);
    }
}
