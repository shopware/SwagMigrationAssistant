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
use GuzzleHttp\Promise\Utils;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\IllegalFileNameException;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\CannotGetFileRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\ProductDownloadDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class HttpProductDownloadProcessor extends BaseMediaService implements MediaFileProcessorInterface
{
    private const STATE_FULFILLED = 'fulfilled';

    private const API_ENDPOINT = 'SwagMigrationEsdFiles/';

    public function __construct(
        private readonly EntityRepository $mediaFileRepo,
        private readonly MediaService $mediaService,
        private readonly LoggingServiceInterface $loggingService,
        private readonly ConnectionFactoryInterface $connectionFactory,
        Connection $dbalConnection
    ) {
        parent::__construct($dbalConnection);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        if ($migrationContext->getDataSet() === null) {
            return false;
        }

        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === ProductDownloadDataSet::getEntity();
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
        /** @var MediaProcessWorkloadStruct[] $mappedWorkload */
        $mappedWorkload = [];
        $runId = $migrationContext->getRunUuid();

        // Map workload with uuids as keys
        foreach ($workload as $work) {
            $mappedWorkload[$work->getMediaId()] = $work;
        }

        $media = $this->getMediaFiles(\array_keys($mappedWorkload), $runId);
        $results = $this->startDownloadProcess($migrationContext, $mappedWorkload, $media, $context);
        $mappedWorkload = $this->processResults($results, $mappedWorkload, $workload, $media, $context, $runId);

        return \array_values($mappedWorkload);
    }

    /**
     * @throws MediaNotFoundException
     */
    private function persistFileToMedia(string $filePath, string $uuid, string $name, Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($filePath, $uuid, $name): void {
            $fileExtension = \pathinfo($filePath, \PATHINFO_EXTENSION);
            $mimeType = \mime_content_type($filePath);
            $streamContext = \stream_context_create([
                'http' => [
                    'follow_location' => 0,
                    'max_redirects' => 0,
                ],
            ]);
            $fileBlob = \file_get_contents($filePath, false, $streamContext);
            $name = \pathinfo($name, \PATHINFO_FILENAME);
            $name = \preg_replace('/[^a-zA-Z0-9_-]+/', '-', \mb_strtolower($name)) ?? Uuid::randomHex();

            if ($fileBlob === false || $mimeType === false) {
                throw new \RuntimeException(\sprintf('Could read file %s.', $filePath));
            }

            try {
                $this->mediaService->saveFile(
                    $fileBlob,
                    $fileExtension,
                    $mimeType,
                    $name,
                    $context,
                    DefaultEntities::PRODUCT_DOWNLOAD,
                    $uuid
                );
            } catch (DuplicatedMediaFileNameException $e) {
                $this->mediaService->saveFile(
                    $fileBlob,
                    $fileExtension,
                    $mimeType,
                    $name . \mb_substr(Uuid::randomHex(), 0, 5),
                    $context,
                    DefaultEntities::PRODUCT_DOWNLOAD,
                    $uuid
                );
            } catch (IllegalFileNameException|EmptyMediaFilenameException $e) {
                $this->mediaService->saveFile(
                    $fileBlob,
                    $fileExtension,
                    $mimeType,
                    $uuid,
                    $context,
                    DefaultEntities::PRODUCT_DOWNLOAD,
                    $uuid
                );
            }
        });
    }

    /**
     * Start all the download requests for the media in parallel (async) and return the promise array.
     *
     * @param MediaProcessWorkloadStruct[] $mappedWorkload
     */
    private function createParallelDownloadMediaRequests(array $media, array &$mappedWorkload, Client $client): array
    {
        $promises = [];
        foreach ($media as $mediaFile) {
            $uuid = \mb_strtolower($mediaFile['media_id']);
            $additionalData = [];
            $additionalData['file_size'] = $mediaFile['file_size'];
            $additionalData['uri'] = $mediaFile['uri'];
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
                self::API_ENDPOINT . \base64_encode($additionalData['uri']),
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
            $updateProcessedMediaFiles[] = [
                'id' => $data['id'],
                'processed' => true,
            ];
        }

        if (!empty($failureUuids)) {
            $mediaFiles = $this->getMediaFiles($failureUuids, $runId);

            foreach ($mediaFiles as $data) {
                $updateProcessedMediaFiles[] = [
                    'id' => $data['id'],
                    'processFailure' => true,
                ];
            }
        }

        if (empty($updateProcessedMediaFiles)) {
            return;
        }

        $this->mediaFileRepo->update($updateProcessedMediaFiles, $context);
    }

    private function startDownloadProcess(MigrationContextInterface $migrationContext, array $mappedWorkload, array $media, Context $context): ?array
    {
        $client = $this->connectionFactory->createApiClient($migrationContext);

        if ($client === null) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $migrationContext->getRunUuid(),
                DefaultEntities::PRODUCT_DOWNLOAD,
                new \Exception('Http client can not connect to server.')
            ));
            $this->loggingService->saveLogging($context);

            return [];
        }

        $promises = $this->createParallelDownloadMediaRequests($media, $mappedWorkload, $client);

        return Utils::settle($promises)->wait();
    }

    private function processResults(
        ?array $results,
        array $mappedWorkload,
        array $workload,
        array $media,
        Context $context,
        string $runId
    ): array {
        if (!\is_array($results)) {
            return [];
        }

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

            if ($state !== self::STATE_FULFILLED) {
                $mappedWorkload[$uuid] = $oldWorkload;
                $mappedWorkload[$uuid]->setAdditionalData($additionalData);
                $mappedWorkload[$uuid]->setErrorCount($mappedWorkload[$uuid]->getErrorCount() + 1);

                if ($mappedWorkload[$uuid]->getErrorCount() > ProcessMediaHandler::MEDIA_ERROR_THRESHOLD) {
                    $failureUuids[] = $uuid;
                    $mappedWorkload[$uuid]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $this->loggingService->addLogEntry(new CannotGetFileRunLog(
                        $mappedWorkload[$uuid]->getRunId(),
                        DefaultEntities::PRODUCT_DOWNLOAD,
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
                // move media to media system
                $filename = $this->getMediaName($media, $uuid);
                $this->persistFileToMedia($filePath, $uuid, $filename, $context);
                \unlink($filePath);
                $finishedUuids[] = $uuid;
            }

            if ($oldWorkload->getErrorCount() === $mappedWorkload[$uuid]->getErrorCount()) {
                $mappedWorkload[$uuid]->setErrorCount(0);
            }
        }

        $this->setProcessedFlag($runId, $context, $finishedUuids, $failureUuids);
        $this->loggingService->saveLogging($context);

        return $mappedWorkload;
    }
}
