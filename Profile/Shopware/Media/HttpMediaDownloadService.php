<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Media;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\IllegalFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\NoFileSystemPermissionsException;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\CannotGetFileRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class HttpMediaDownloadService extends BaseMediaService implements MediaFileProcessorInterface
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
        LoggingServiceInterface $loggingService,
        Connection $dbalConnection
    ) {
        $this->mediaFileRepo = $migrationMediaFileRepo;
        $this->fileSaver = $fileSaver;
        $this->loggingService = $loggingService;
        parent::__construct($dbalConnection);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === MediaDataSet::getEntity();
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

        if (!\is_dir('_temp') && !\mkdir('_temp') && !\is_dir('_temp')) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $runId,
                DefaultEntities::MEDIA,
                new NoFileSystemPermissionsException()
            ));
            $this->loggingService->saveLogging($context);

            return $workload;
        }

        //Fetch media from database
        $media = $this->getMediaFiles($mediaIds, $runId);

        //Do download requests and store the promises
        $client = new Client([
            'verify' => false,
        ]);
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

            $oldWorkloadSearchResult = \array_filter(
                $workload,
                function (MediaProcessWorkloadStruct $work) use ($uuid) {
                    return $work->getMediaId() === $uuid;
                }
            );

            /** @var MediaProcessWorkloadStruct $oldWorkload */
            $oldWorkload = \array_pop($oldWorkloadSearchResult);

            if ($state !== 'fulfilled') {
                $mappedWorkload[$uuid] = $oldWorkload;
                $mappedWorkload[$uuid]->setAdditionalData($additionalData);
                $mappedWorkload[$uuid]->setErrorCount($mappedWorkload[$uuid]->getErrorCount() + 1);

                if ($mappedWorkload[$uuid]->getErrorCount() > ProcessMediaHandler::MEDIA_ERROR_THRESHOLD) {
                    $failureUuids[] = $uuid;
                    $mappedWorkload[$uuid]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $this->loggingService->addLogEntry(new CannotGetFileRunLog(
                        $mappedWorkload[$uuid]->getRunId(),
                        DefaultEntities::MEDIA,
                        $mappedWorkload[$uuid]->getMediaId(),
                        $mappedWorkload[$uuid]->getAdditionalData()['uri']
                    ));
                }

                continue;
            }

            $response = $result['value'];
            $fileExtension = $additionalData['file_extension'] ?? \pathinfo($additionalData['uri'], \PATHINFO_EXTENSION);
            $filePath = \sprintf('_temp/%s.%s', $uuid, $fileExtension);

            $streamContext = \stream_context_create([
                'http' => [
                    'follow_location' => 0,
                    'max_redirects' => 0,
                ],
            ]);
            $fileHandle = \fopen($filePath, 'ab', false, $streamContext);

            if (!\is_resource($fileHandle)) {
                throw new \RuntimeException(\sprintf('Could not open file %s in mode %s.', $filePath, 'ab'));
            }

            \fwrite($fileHandle, $response->getBody()->getContents());
            \fclose($fileHandle);

            if ($mappedWorkload[$uuid]->getState() === MediaProcessWorkloadStruct::FINISH_STATE) {
                //move media to media system
                $filename = $this->getMediaName($media, $uuid);

                try {
                    $this->persistFileToMedia(
                        $filePath,
                        $uuid,
                        $filename,
                        (int) $additionalData['file_size'],
                        $fileExtension,
                        $context
                    );
                    $finishedUuids[] = $uuid;
                } catch (\Exception $e) {
                    $failureUuids[] = $uuid;
                    $mappedWorkload[$uuid]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $this->loggingService->addLogEntry(new ExceptionRunLog(
                        $mappedWorkload[$uuid]->getRunId(),
                        DefaultEntities::MEDIA,
                        $e,
                        $uuid
                    ));
                }

                \unlink($filePath);
            }

            if ($oldWorkload->getErrorCount() === $mappedWorkload[$uuid]->getErrorCount()) {
                $mappedWorkload[$uuid]->setErrorCount(0);
            }
        }

        $this->setProcessedFlag($runId, $context, $finishedUuids, $failureUuids);
        $this->loggingService->saveLogging($context);

        return \array_values($mappedWorkload);
    }

    /**
     * Start all the download requests for the media in parallel (async) and return the promise array.
     *
     * @param MediaProcessWorkloadStruct[] $mappedWorkload
     */
    protected function doMediaDownloadRequests(array $media, array &$mappedWorkload, Client $client): array
    {
        $promises = [];
        foreach ($media as $mediaFile) {
            $uuid = \mb_strtolower($mediaFile['media_id']);
            $additionalData = [];
            $additionalData['file_size'] = $mediaFile['file_size'];
            $additionalData['uri'] = $mediaFile['uri'];
            $mappedWorkload[$uuid]->setAdditionalData($additionalData);

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
    protected function persistFileToMedia(string $filePath, string $uuid, string $name, int $fileSize, string $fileExtension, Context $context): void
    {
        $mimeType = \mime_content_type($filePath);
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        $name = \preg_replace('/[^a-zA-Z0-9_-]+/', '-', \mb_strtolower($name));

        try {
            $this->fileSaver->persistFileToMedia($mediaFile, $name, $uuid, $context);
        } catch (DuplicatedMediaFileNameException $e) {
            $this->fileSaver->persistFileToMedia(
                $mediaFile,
                $name . \mb_substr(Uuid::randomHex(), 0, 5),
                $uuid,
                $context
            );
        } catch (IllegalFileNameException | EmptyMediaFilenameException $e) {
            $this->fileSaver->persistFileToMedia($mediaFile, Uuid::randomHex(), $uuid, $context);
        }
    }

    protected function doNormalDownloadRequest(MediaProcessWorkloadStruct $workload, Client $client): ?Promise\PromiseInterface
    {
        $additionalData = $workload->getAdditionalData();

        try {
            $promise = $client->getAsync(
                $additionalData['uri'],
                [
                    'query' => ['alt' => 'media'],
                ]
            );

            $workload->setCurrentOffset((int) $additionalData['file_size']);
            $workload->setState(MediaProcessWorkloadStruct::FINISH_STATE);
        } catch (\Throwable $exception) {
            $promise = null;
            $workload->setErrorCount($workload->getErrorCount() + 1);
        }

        return $promise;
    }

    private function getMediaName(array $media, string $mediaId): string
    {
        foreach ($media as $mediaFile) {
            if ($mediaFile['media_id'] === $mediaId) {
                return $mediaFile['file_name'];
            }
        }

        return '';
    }

    private function setProcessedFlag(string $runId, Context $context, array $finishedUuids, array $failureUuids): void
    {
        $mediaFiles = $this->getMediaFiles($finishedUuids, $runId);
        $updateProcessedMediaFiles = [];
        foreach ($mediaFiles as $data) {
            if (!\in_array($data['media_id'], $failureUuids, true)) {
                $updateProcessedMediaFiles[] = [
                    'id' => $data['id'],
                    'processed' => true,
                ];
            }
        }

        if (empty($updateProcessedMediaFiles)) {
            return;
        }

        $this->mediaFileRepo->update($updateProcessedMediaFiles, $context);
    }
}
