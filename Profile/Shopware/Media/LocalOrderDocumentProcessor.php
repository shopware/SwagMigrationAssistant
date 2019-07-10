<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Media;

use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\NoFileSystemPermissionsException;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\OrderDocumentDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Logging\LogTypes;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class LocalOrderDocumentProcessor implements MediaFileProcessorInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var MediaService
     */
    private $mediaService;

    public function __construct(
        EntityRepositoryInterface $migrationMediaFileRepo,
        MediaService $mediaService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mediaFileRepo = $migrationMediaFileRepo;
        $this->mediaService = $mediaService;
        $this->loggingService = $loggingService;
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === OrderDocumentDataSet::getEntity();
    }

    public function process(
        MigrationContextInterface $migrationContext,
        Context $context,
        array $workload,
        int $fileChunkByteSize
    ): array {
        $mappedWorkload = [];
        $runId = $migrationContext->getRunUuid();

        foreach ($workload as $work) {
            $mappedWorkload[$work->getMediaId()] = $work;
        }

        if (!is_dir('_temp') && !mkdir('_temp') && !is_dir('_temp')) {
            $exception = new NoFileSystemPermissionsException();
            $this->loggingService->addError($runId, (string) $exception->getCode(), '', $exception->getMessage());
            $this->loggingService->saveLogging($context);

            return $workload;
        }

        /** @var SwagMigrationMediaFileEntity[] $media */
        $media = $this->getMediaFiles(array_keys($mappedWorkload), $migrationContext->getRunUuid(), $context);

        return $this->copyMediaFiles($media, $mappedWorkload, $migrationContext, $context);
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
     * @param MediaProcessWorkloadStruct[] $mappedWorkload
     *
     * @return MediaProcessWorkloadStruct[]
     */
    private function copyMediaFiles(
        array $media,
        array $mappedWorkload,
        MigrationContextInterface $migrationContext,
        Context $context
    ): array {
        $processedMedia = [];

        /** @var SwagMigrationMediaFileEntity[] $media */
        foreach ($media as $mediaFile) {
            $sourcePath = $migrationContext->getConnection()->getCredentialFields()['installationRoot'] . '/files/documents/' . $mediaFile->getFileName() . '.pdf';

            if (!file_exists($sourcePath)) {
                $mappedWorkload[$mediaFile->getMediaId()]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                $this->loggingService->addError(
                    $mappedWorkload[$mediaFile->getMediaId()]->getRunId(),
                    LogTypes::SOURCE_FILE_NOT_FOUND,
                    '',
                    'File not found in source system.',
                    [
                        'path' => $sourcePath,
                    ]
                );
                $processedMedia[] = $mediaFile->getMediaId();

                continue;
            }

            $mappedWorkload[$mediaFile->getMediaId()]->setState(MediaProcessWorkloadStruct::FINISH_STATE);
            $this->persistFileToMedia($sourcePath, $mediaFile, $context);
            $processedMedia[] = $mediaFile->getMediaId();
        }

        $this->setProcessedFlag($migrationContext->getRunUuid(), $context, $processedMedia);
        $this->loggingService->saveLogging($context);

        return array_values($mappedWorkload);
    }

    private function persistFileToMedia(
        string $sourcePath,
        SwagMigrationMediaFileEntity $media,
        Context $context
    ): void {
        $fileExtension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $mimeType = mime_content_type($sourcePath);
        $fileBlob = file_get_contents($sourcePath);

        try {
            $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($fileBlob, $fileExtension, $mimeType, $media) {
                $this->mediaService->saveFile(
                    $fileBlob,
                    $fileExtension,
                    $mimeType,
                    $media->getFileName(),
                    $context,
                    'document',
                    $media->getMediaId()
                );
            });
        } catch (DuplicatedMediaFileNameException $e) {
            $this->mediaService->saveFile(
                $fileBlob,
                $fileExtension,
                $mimeType,
                $media->getFileName() . substr(Uuid::randomHex(), 0, 5),
                $context,
                'document',
                $media->getMediaId()
            );
        }
    }

    private function setProcessedFlag(string $runId, Context $context, array $finishedUuids): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $finishedUuids));
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        $updateableMediaEntities = [];
        foreach ($mediaFiles->getElements() as $mediaFile) {
            /* @var SwagMigrationMediaFileEntity $mediaFile */
            $updateableMediaEntities[] = [
                'id' => $mediaFile->getId(),
                'processed' => true,
            ];
        }

        if (empty($updateableMediaEntities)) {
            return;
        }

        $this->mediaFileRepo->update($updateableMediaEntities, $context);
    }
}
