<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Media;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\MediaService;
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
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Api\Shopware55ApiGateway;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class HttpOrderDocumentProcessor extends AbstractMediaFileProcessor
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(
        EntityRepositoryInterface $migrationMediaFileRepo,
        MediaService $mediaService,
        LoggingServiceInterface $loggingService,
        ConnectionFactoryInterface $connectionFactory
    ) {
        $this->mediaFileRepo = $migrationMediaFileRepo;
        $this->mediaService = $mediaService;
        $this->loggingService = $loggingService;
        $this->connectionFactory = $connectionFactory;
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function getSupportedGatewayIdentifier(): string
    {
        return Shopware55ApiGateway::GATEWAY_NAME;
    }

    public function getSupportedEntity(): string
    {
        return OrderDocumentDataSet::getEntity();
    }

    /**
     * @param MediaProcessWorkloadStruct[] $workload
     *
     * @throws MediaNotFoundException
     * @throws InconsistentCriteriaIdsException
     *
     * @return MediaProcessWorkloadStruct[]
     */
    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array
    {
        //Map workload with uuids as keys
        /** @var MediaProcessWorkloadStruct[] $mappedWorkload */
        $mappedWorkload = [];
        $mediaIds = [];
        $runId = $migrationContext->getRunUuid();

        foreach ($workload as $work) {
            $mappedWorkload[$work->getMediaId()] = $work;
            $mediaIds[] = $work->getMediaId();
        }

        if (!is_dir('_temp') && !mkdir('_temp') && !is_dir('_temp')) {
            $exception = new NoFileSystemPermissionsException();
            $this->loggingService->addError($runId, (string) $exception->getCode(), '', $exception->getMessage());
            $this->loggingService->saveLogging($context);

            return $workload;
        }

        //Fetch media from database
        /** @var SwagMigrationMediaFileEntity[] $media */
        $media = $this->getMediaFiles(array_keys($mappedWorkload), $migrationContext->getRunUuid(), $context);

        //Do download requests and store the promises
        $client = $this->connectionFactory->createApiClient($migrationContext);
        $promises = $this->doMediaDownloadRequests($media, $mappedWorkload, $client);

        // Wait for the requests to complete, even if some of them fail
        /** @var array $results */
        $results = Promise\settle($promises)->wait();

        //handle responses
        $failureUuids = [];
        $finishedUuids = [];
        foreach ($results as $uuid => $result) {
            $state = $result['state'];
            $additionalData = $mappedWorkload[$uuid]->getAdditionalData();

            $oldWorkloadSearchResult = array_filter(
                $workload,
                function (MediaProcessWorkloadStruct $work) use ($uuid) {
                    return $work->getMediaId() === $uuid;
                }
            );

            /** @var MediaProcessWorkloadStruct $oldWorkload */
            $oldWorkload = array_pop($oldWorkloadSearchResult);

            if ($state !== 'fulfilled') {
                $mappedWorkload[$uuid] = $oldWorkload;
                $mappedWorkload[$uuid]->setAdditionalData($additionalData);
                $mappedWorkload[$uuid]->setErrorCount($mappedWorkload[$uuid]->getErrorCount() + 1);

                if ($mappedWorkload[$uuid]->getErrorCount() > ProcessMediaHandler::MEDIA_ERROR_THRESHOLD) {
                    $failureUuids[] = $uuid;
                    $mappedWorkload[$uuid]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $this->loggingService->addError(
                        $mappedWorkload[$uuid]->getRunId(),
                        Shopware55LogTypes::CANNOT_DOWNLOAD_ORDER_DOCUMENT,
                        '',
                        'Cannot download order document.',
                        [
                            'mediaId' => $mappedWorkload[$uuid]->getMediaId(),
                            'uri' => $mappedWorkload[$uuid]->getAdditionalData()['uri'],
                        ]
                    );
                }

                continue;
            }

            $response = $result['value'];
            $filePath = sprintf('_temp/%s.%s', $uuid, 'pdf');

            $fileHandle = fopen($filePath, 'ab');
            fwrite($fileHandle, $response->getBody()->getContents());
            fclose($fileHandle);

            if ($mappedWorkload[$uuid]->getState() === MediaProcessWorkloadStruct::FINISH_STATE) {
                //move media to media system
                $filename = $this->getMediaName($media, $uuid);
                $this->persistFileToMedia($filePath, $uuid, $filename, $context);
                unlink($filePath);
                $finishedUuids[] = $uuid;
            }

            if ($oldWorkload->getErrorCount() === $mappedWorkload[$uuid]->getErrorCount()) {
                $mappedWorkload[$uuid]->setErrorCount(0);
            }
        }

        $this->setProcessedFlag($runId, $context, $finishedUuids, $failureUuids);
        $this->loggingService->saveLogging($context);

        return array_values($mappedWorkload);
    }

    private function getMediaFiles(array $mediaIds, string $runId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaIds));
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $mediaSearchResult = $this->mediaFileRepo->search($criteria, $context);

        return $mediaSearchResult->getElements();
    }

    /**
     * @throws MediaNotFoundException
     */
    private function persistFileToMedia(string $filePath, string $uuid, string $name, Context $context): void
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = mime_content_type($filePath);
        $fileBlob = file_get_contents($filePath);

        try {
            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($fileBlob, $fileExtension, $mimeType, $uuid, $name) {
                $this->mediaService->saveFile(
                    $fileBlob,
                    $fileExtension,
                    $mimeType,
                    $name,
                    $context,
                    'document',
                    $uuid
                );
            });
        } catch (DuplicatedMediaFileNameException $e) {
            $this->mediaService->saveFile(
                $fileBlob,
                $fileExtension,
                $mimeType,
                $name . substr(Uuid::randomHex(), 0, 5),
                $context,
                'document',
                $uuid
            );
        }
    }

    /**
     * Start all the download requests for the media in parallel (async) and return the promise array.
     *
     * @param SwagMigrationMediaFileEntity[] $media
     * @param MediaProcessWorkloadStruct[]   $mappedWorkload
     */
    private function doMediaDownloadRequests(array $media, array &$mappedWorkload, Client $client): array
    {
        $promises = [];
        foreach ($media as $mediaFile) {
            $uuid = strtolower($mediaFile->getMediaId());
            $additionalData = [];
            $additionalData['file_size'] = $mediaFile->getFileSize();
            $additionalData['uri'] = $mediaFile->getUri();
            $mappedWorkload[$uuid]->setAdditionalData($additionalData);

            $promise = $this->doDownloadRequest($mappedWorkload[$uuid], $client);

            if ($promise !== null) {
                $promises[$uuid] = $promise;
            }
        }

        return $promises;
    }

    private function doDownloadRequest(MediaProcessWorkloadStruct $workload, Client $client): ?Promise\PromiseInterface
    {
        $additionalData = $workload->getAdditionalData();

        try {
            $promise = $client->getAsync(
                'SwagMigrationOrderDocuments/' . $additionalData['uri']
            );

            $workload->setCurrentOffset($additionalData['file_size']);
            $workload->setState(MediaProcessWorkloadStruct::FINISH_STATE);
        } catch (\Exception $exception) {
            $promise = null;
            $workload->setErrorCount($workload->getErrorCount() + 1);
        }

        return $promise;
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
