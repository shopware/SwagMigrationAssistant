<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Media;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Logging\Log\CannotGetFileRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\Media\Processor\BaseMediaService;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MessageQueue\Handler\ProcessMediaHandler;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware6\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

#[Package('services-settings')]
class HttpOrderDocumentGenerationService extends BaseMediaService implements MediaFileProcessorInterface
{
    private SwagMigrationConnectionEntity $connection;

    /**
     * @param EntityRepository<DocumentCollection> $documentRepository
     * @param EntityRepository<SwagMigrationMediaFileCollection> $migrationMediaFileRepo
     */
    public function __construct(
        private readonly EntityRepository $documentRepository,
        EntityRepository $migrationMediaFileRepo,
        private readonly LoggingServiceInterface $loggingService,
        private readonly MappingServiceInterface $mappingService,
        private readonly MediaService $mediaService,
        private readonly ConnectionFactoryInterface $connectionFactory,
        Connection $dbalConnection
    ) {
        parent::__construct($dbalConnection, $migrationMediaFileRepo);
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
        array $workload
    ): array {
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

        $documents = $this->getMediaFiles($documentIds, $runId);

        // Do download requests and store the promises
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
        $results = Utils::settle($promises)->wait();

        // handle responses
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

            $oldWorkload = \array_pop($oldWorkloadSearchResult);

            if ($oldWorkload === null) {
                continue;
            }

            if ($state !== 'fulfilled') {
                $this->handleFailedRequest($oldWorkload, $mappedWorkload[$uuid], $uuid, $additionalData, $failureUuids, $result['reason'] ?? null);

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

    /**
     * @param array<array<string, mixed>> $documents
     * @param array<MediaProcessWorkloadStruct> $mappedWorkload
     *
     * @return array<string, PromiseInterface>
     */
    private function downloadDocument(array $documents, array $mappedWorkload, HttpClientInterface $client): array
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

    /**
     * @param array<string, mixed> $result
     * @param list<string> $finishedUuids
     */
    private function handleCompleteRequest(Context $context, array $result, string $documentId, array &$finishedUuids): void
    {
        /** @var Response $response */
        $response = $result['value'];
        $arrayResponse = \json_decode($response->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);

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

    /**
     * @param array<string, mixed> $additionalData
     * @param list<string> $failureUuids
     */
    private function handleFailedRequest(
        MediaProcessWorkloadStruct $oldWorkload,
        MediaProcessWorkloadStruct &$mappedWorkload,
        string $uuid,
        array $additionalData,
        array &$failureUuids,
        ?RequestException $clientException = null
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
                $mappedWorkload->getAdditionalData()['uri'],
                $clientException
            ));
        }
    }
}
