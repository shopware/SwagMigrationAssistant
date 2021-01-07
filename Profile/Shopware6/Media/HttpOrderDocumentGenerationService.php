<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Media;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Promise;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use SwagMigrationAssistant\Exception\NoFileSystemPermissionsException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\CannotGetFileRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\Media\BaseMediaService;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\AuthClient;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

class HttpOrderDocumentGenerationService extends BaseMediaService implements MediaFileProcessorInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationMediaFileRepo;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(
        EntityRepositoryInterface $documentRepo,
        EntityRepositoryInterface $migrationMediaFileRepo,
        LoggingServiceInterface $loggingService,
        MappingServiceInterface $mappingService,
        MediaService $mediaService,
        ConnectionFactoryInterface $connectionFactory,
        Connection $dbalConnection
    ) {
        $this->documentRepository = $documentRepo;
        $this->migrationMediaFileRepo = $migrationMediaFileRepo;
        $this->loggingService = $loggingService;
        $this->mappingService = $mappingService;
        $this->mediaService = $mediaService;
        $this->connectionFactory = $connectionFactory;
        $this->dbalConnection = $dbalConnection;
        parent::__construct($dbalConnection);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware6ProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::ORDER_DOCUMENT_GENERATED;
    }

    public function process(
        MigrationContextInterface $migrationContext,
        Context $context,
        array $workload,
        int $fileChunkByteSize = 0
    ): array {
        /** @var MediaProcessWorkloadStruct[] $mappedWorkload */
        $mappedWorkload = [];
        $documentIds = [];
        $runId = $migrationContext->getRunUuid();
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return $workload;
        }

        $this->connection = $connection;

        foreach ($workload as $work) {
            $mappedWorkload[$work->getMediaId()] = $work;
            $documentIds[] = $work->getMediaId();
        }

        if (!\is_dir('_temp') && !\mkdir('_temp') && !\is_dir('_temp')) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $runId,
                DefaultEntities::ORDER_DOCUMENT_GENERATED,
                new NoFileSystemPermissionsException()
            ));
            $this->loggingService->saveLogging($context);

            return $workload;
        }

        $documents = $this->getMediaFiles($documentIds, $runId);

        //Do download requests and store the promises
        $client = $this->connectionFactory->createApiClient($migrationContext);

        if ($client === null) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $runId,
                DefaultEntities::ORDER_DOCUMENT_GENERATED,
                new \Exception('Connection to the source system could not be established')
            ));
            $this->loggingService->saveLogging($context);

            return $workload;
        }

        $promises = $this->downloadDocument($documents, $mappedWorkload, $client);

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
                $this->handleFailedRequest($oldWorkload, $mappedWorkload[$uuid], $uuid, $additionalData, $failureUuids);

                continue;
            }

            if ($mappedWorkload[$uuid]->getState() === MediaProcessWorkloadStruct::FINISH_STATE) {
                $this->handleCompleteRequest($context, $result, $uuid, $finishedUuids);
            }

            if ($oldWorkload->getErrorCount() === $mappedWorkload[$uuid]->getErrorCount()) {
                $mappedWorkload[$uuid]->setErrorCount(0);
            }
        }

        $this->setProcessedFlag($runId, $context, $finishedUuids, $failureUuids);
        $this->loggingService->saveLogging($context);

        return \array_values($mappedWorkload);
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

        $this->migrationMediaFileRepo->update($updateProcessedMediaFiles, $context);
    }

    /**
     * @param MediaProcessWorkloadStruct[] $mappedWorkload
     */
    private function downloadDocument(array $documents, array $mappedWorkload, AuthClient $client): array
    {
        $promises = [];
        foreach ($documents as $document) {
            $uuid = \mb_strtolower($document['media_id']);
            $workload = $mappedWorkload[$uuid];
            $additionalData = [];
            $additionalData['uri'] = $document['uri'];

            $workload->setAdditionalData($additionalData);
            $additionalData = $workload->getAdditionalData();

            try {
                $promise = $client->getAsync(
                    $additionalData['uri'],
                    [
                        'query' => ['identifier' => $uuid],
                    ]
                );

                $workload->setState(MediaProcessWorkloadStruct::FINISH_STATE);
            } catch (\Throwable $exception) {
                $promise = null;
                $workload->setErrorCount($workload->getErrorCount() + 1);
            }

            if ($promise !== null) {
                $promises[$uuid] = $promise;
            }
        }

        return $promises;
    }

    private function handleCompleteRequest(Context $context, array $result, string $documentId, array &$finishedUuids): void
    {
        $response = $result['value'];
        $arrayResponse = \json_decode($response->getBody()->getContents(), true);

        $shownArray = $arrayResponse;
        unset($shownArray['file_blob']);

        $fileName = $arrayResponse['file_name'];
        $fileContentType = $arrayResponse['file_content_type'];
        $fileBlob = $arrayResponse['file_blob'];

        $mapping = $this->mappingService->getMapping(
            $this->connection->getId(),
            DefaultEntities::ORDER_DOCUMENT_GENERATED_MEDIA,
            $documentId,
            $context
        );

        $mediaId = null;
        if ($mapping !== null) {
            $mediaId = $mapping['entityUuid'];
        }

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use (
            $fileBlob,
            $fileContentType,
            $fileName,
            &$mediaId
        ): void {
            $clearFileBlob = \base64_decode($fileBlob, true);

            if ($clearFileBlob === false) {
                return;
            }

            $mediaId = $this->mediaService->saveFile(
                $clearFileBlob,
                'pdf',
                $fileContentType,
                $fileName,
                $context,
                'document',
                $mediaId
            );
        });

        $this->mappingService->getOrCreateMapping(
            $this->connection->getId(),
            DefaultEntities::ORDER_DOCUMENT_GENERATED_MEDIA,
            $documentId,
            $context,
            null,
            null,
            $mediaId
        );
        $this->mappingService->writeMapping($context);

        $this->documentRepository->update(
            [
                [
                    'id' => $documentId,
                    'documentMediaFileId' => $mediaId,
                ],
            ],
            $context
        );

        $finishedUuids[] = $documentId;
    }

    private function handleFailedRequest(
        MediaProcessWorkloadStruct $oldWorkload,
        MediaProcessWorkloadStruct &$mappedWorkload,
        string $uuid,
        array $additionalData,
        array &$failureUuids
    ): void {
        $mappedWorkload = $oldWorkload;
        $mappedWorkload->setAdditionalData($additionalData);
        $mappedWorkload->setErrorCount($mappedWorkload->getErrorCount() + 1);

        if ($mappedWorkload->getErrorCount() > ProcessMediaHandler::MEDIA_ERROR_THRESHOLD) {
            $failureUuids[] = $uuid;
            $mappedWorkload->setState(MediaProcessWorkloadStruct::ERROR_STATE);
            $this->loggingService->addLogEntry(new CannotGetFileRunLog(
                $mappedWorkload->getRunId(),
                DefaultEntities::ORDER_DOCUMENT,
                $mappedWorkload->getMediaId(),
                $mappedWorkload->getAdditionalData()['uri']
            ));
        }
    }
}
