<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use Shopware\Core\Content\Media\Exception\IllegalMimeTypeException;
use Shopware\Core\Content\Media\Exception\UploadException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\TermQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\TermsQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Exception\NoFileSystemPermissionsException;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\SwagMigrationMappingStruct;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class HttpAssetDownloadService implements HttpAssetDownloadServiceInterface
{
    private const ASSET_ERROR_THRESHOLD = 3;

    /**
     * @var FileSaver
     */
    private $fileSaver;

    /**
     * @var RepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    public function __construct(
        RepositoryInterface $migrationMediaFileRepo,
        FileSaver $fileSaver,
        LoggingServiceInterface $loggingService
    ) {
        $this->mediaFileRepo = $migrationMediaFileRepo;
        $this->fileSaver = $fileSaver;
        $this->loggingService = $loggingService;
    }

    public function fetchMediaUuids(Context $context, string $runId, int $limit): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('runId', $runId));
        $criteria->addFilter(new TermQuery('written', true));
        $criteria->addFilter(new TermQuery('downloaded', false));
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));
        $migrationData = $this->mediaFileRepo->search($criteria, $context);

        $mediaUuids = [];
        foreach ($migrationData->getElements() as $mediaFile) {
            /* @var SwagMigrationMediaFileStruct $mediaFile */
            $mediaUuids[] = $mediaFile->getMediaId();
        }

        return $mediaUuids;
    }

    /**
     * @param array $workload [{ "uuid": "04ed51ccbb2341bc9b352d78e64213fb", "currentOffset": 0, "state": "inProgress" }]
     *
     * @throws IllegalMimeTypeException
     * @throws NoFileSystemPermissionsException
     * @throws UploadException
     */
    public function downloadAssets(string $runId, Context $context, array $workload, int $fileChunkByteSize): array
    {
        //Map workload with uuids as keys
        $mappedWorkload = [];
        $mediaIds = [];
        foreach ($workload as $work) {
            $mappedWorkload[$work['uuid']] = $work;
            $runId = $work['runId'];
            $mediaIds[] = $work['uuid'];
        }

        if (!is_dir('_temp') && !mkdir('_temp') && !is_dir('_temp')) {
            $exception = new NoFileSystemPermissionsException();
            $this->loggingService->addError($runId, (string) $exception->getCode(), '', $exception->getMessage());
            $this->loggingService->saveLogging($context);

            return $workload;
        }

        //Map workload with uuids as keys
        $mappedWorkload = [];
        $mediaIds = [];
        foreach ($workload as $work) {
            $mappedWorkload[$work['uuid']] = $work;
            $mediaIds[] = $work['uuid'];
        }

        //Fetch assets from database
        $client = new Client([
            'verify' => false,
        ]);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaIds));
        $assetSearchResult = $this->mediaFileRepo->search($criteria, $context);
        /** @var SwagMigrationMappingStruct[] $assets */
        $assets = $assetSearchResult->getElements();

        //Do download requests and store the promises
        $promises = $this->doAssetDownloadRequests($assets, $fileChunkByteSize, $mappedWorkload, $client);

        // Wait for the requests to complete, even if some of them fail
        /** @var array $results */
        $results = Promise\settle($promises)->wait();

        //handle responses
        $finishedUuids = [];
        foreach ($results as $uuid => $result) {
            /** @var Response $response */
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
                    $finishedUuids[] = $uuid;
                } else {
                    $mappedWorkload[$uuid]['errorCount'] = 1;
                }

                if ($mappedWorkload[$uuid]['errorCount'] > self::ASSET_ERROR_THRESHOLD) {
                    $mappedWorkload[$uuid]['state'] = 'error';
                    $this->loggingService->addError(
                        $mappedWorkload[$uuid]['runId'],
                        (string) SymfonyResponse::HTTP_REQUEST_TIMEOUT,
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
                //move asset to media system
                $this->persistFileToMedia($filePath, $uuid, (int) $additionalData['file_size'], $fileExtension, $context);
                unlink($filePath);
                $finishedUuids[] = $uuid;
            }

            if (isset($mappedWorkload[$uuid]['errorCount'], $oldWorkload['errorCount']) &&
                $oldWorkload['errorCount'] === $mappedWorkload[$uuid]['errorCount']) {
                unset($mappedWorkload[$uuid]['errorCount']);
            }
        }

        $this->setDownloadedFlag($runId, $context, $finishedUuids);
        $this->loggingService->saveLogging($context);

        return array_values($mappedWorkload);
    }

    /**
     * Start all the download requests for the assets in parallel (async) and return the promise array.
     *
     * @param SwagMigrationMediaFileStruct[] $assets
     */
    private function doAssetDownloadRequests(array $assets, int $fileChunkByteSize, array &$mappedWorkload, Client $client): array
    {
        $promises = [];
        foreach ($assets as $asset) {
            $uuid = strtolower($asset->getMediaId());
            $additionalData = [];
            $additionalData['file_size'] = $asset->getFileSize();
            $additionalData['uri'] = $asset->getUri();
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
     * Persists the file to the flysystem.
     *
     * @throws IllegalMimeTypeException
     * @throws UploadException
     */
    private function persistFileToMedia(string $filePath, string $uuid, int $fileSize, string $fileExtension, Context $context): void
    {
        $mimeType = mime_content_type($filePath);
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        $this->fileSaver->persistFileToMedia($mediaFile, $uuid, $context);
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
        } catch (Exception $exception) {
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

            //check if chunk is big enough to finish the download of that asset
            if ($chunkEnd < $additionalData['file_size']) {
                $workload['state'] = 'inProgress';
                $workload['currentOffset'] = $chunkEnd + 1;
            } else {
                $workload['state'] = 'finished';
                $workload['currentOffset'] = $additionalData['file_size'];
            }
        } catch (Exception $exception) {
            $promise = null;
            if (isset($workload['errorCount'])) {
                ++$workload['errorCount'];
            } else {
                $workload['errorCount'] = 1;
            }
        }

        return $promise;
    }

    private function setDownloadedFlag(string $runId, Context $context, array $finishedUuids): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermsQuery('mediaId', $finishedUuids));
        $criteria->addFilter(new TermQuery('runId', $runId));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        $updateDownloadedMediaFiles = [];
        foreach ($mediaFiles->getElements() as $data) {
            /* @var SwagMigrationMediaFileStruct $data */
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
