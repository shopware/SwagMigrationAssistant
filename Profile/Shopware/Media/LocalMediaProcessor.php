<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Media;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\IllegalFileNameException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\NoFileSystemPermissionsException;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\CannotGetFileRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Media\Strategy\StrategyResolverInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class LocalMediaProcessor extends BaseMediaService implements MediaFileProcessorInterface
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

    /**
     * @var StrategyResolverInterface[]
     */
    private $resolver;

    /**
     * @param StrategyResolverInterface[] $resolver
     */
    public function __construct(
        EntityRepositoryInterface $migrationMediaFileRepo,
        FileSaver $fileSaver,
        LoggingServiceInterface $loggingService,
        iterable $resolver,
        Connection $dbalConnection
    ) {
        $this->mediaFileRepo = $migrationMediaFileRepo;
        $this->fileSaver = $fileSaver;
        $this->loggingService = $loggingService;
        $this->resolver = $resolver;
        parent::__construct($dbalConnection);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === MediaDataSet::getEntity();
    }

    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array
    {
        $mappedWorkload = [];
        $runId = $migrationContext->getRunUuid();

        foreach ($workload as $work) {
            $mappedWorkload[$work->getMediaId()] = $work;
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

        $media = $this->getMediaFiles(\array_keys($mappedWorkload), $migrationContext->getRunUuid());
        $mappedWorkload = $this->getMediaPathMapping($media, $mappedWorkload, $migrationContext);

        return $this->copyMediaFiles($media, $mappedWorkload, $migrationContext, $context);
    }

    /**
     * @param MediaProcessWorkloadStruct[] $mappedWorkload
     *
     * @return MediaProcessWorkloadStruct[]
     */
    private function getMediaPathMapping(array $media, array $mappedWorkload, MigrationContextInterface $migrationContext): array
    {
        foreach ($media as $mediaFile) {
            $resolver = $this->getResolver($mediaFile, $migrationContext);

            if (!$resolver) {
                $mappedWorkload[$mediaFile['media_id']]->setAdditionalData(['path' => $mediaFile['uri']]);

                continue;
            }
            $path = $resolver->resolve($mediaFile['uri'], $migrationContext);
            $mappedWorkload[$mediaFile['media_id']]->setAdditionalData(['path' => $path]);
        }

        return $mappedWorkload;
    }

    private function getResolver(array $mediaFile, MigrationContextInterface $migrationContext): ?StrategyResolverInterface
    {
        foreach ($this->resolver as $resolver) {
            if ($resolver->supports($mediaFile['uri'], $migrationContext)) {
                return $resolver;
            }
        }

        return null;
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
        $failureUuids = [];

        foreach ($media as $mediaFile) {
            $rowId = $mediaFile['id'];
            $mediaId = $mediaFile['media_id'];
            $sourcePath = $mappedWorkload[$mediaId]->getAdditionalData()['path'];

            if (!\file_exists($sourcePath)) {
                $resolver = $this->getResolver($mediaFile, $migrationContext);

                if ($resolver === null) {
                    $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $this->loggingService->addLogEntry(new CannotGetFileRunLog(
                        $mappedWorkload[$mediaId]->getRunId(),
                        DefaultEntities::MEDIA,
                        $mediaId,
                        $sourcePath
                    ));
                    $processedMedia[] = $mediaId;
                    $failureUuids[$rowId] = $mediaId;

                    continue;
                }
            }

            $fileExtension = \pathinfo($sourcePath, \PATHINFO_EXTENSION);
            $filePath = \sprintf('_temp/%s.%s', $rowId, $fileExtension);

            if (\copy($sourcePath, $filePath)) {
                $fileSize = \filesize($filePath);
                $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::FINISH_STATE);

                try {
                    $this->persistFileToMedia($filePath, $mediaFile, $fileSize, $fileExtension, $context);
                } catch (\Exception $e) {
                    $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $failureUuids[$rowId] = $mediaId;
                    $this->loggingService->addLogEntry(new ExceptionRunLog(
                        $mappedWorkload[$mediaId]->getRunId(),
                        DefaultEntities::MEDIA,
                        $e,
                        $mediaId
                    ));
                }
                \unlink($filePath);
            } else {
                $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                $this->loggingService->addLogEntry(new CannotGetFileRunLog(
                    $mappedWorkload[$mediaId]->getRunId(),
                    DefaultEntities::MEDIA,
                    $mediaId,
                    $sourcePath
                ));
                $failureUuids[$rowId] = $mediaId;
            }
            $processedMedia[] = $mediaId;
        }
        $this->setProcessedFlag($migrationContext->getRunUuid(), $context, $processedMedia, $failureUuids);
        $this->loggingService->saveLogging($context);

        return \array_values($mappedWorkload);
    }

    private function persistFileToMedia(
        string $filePath,
        array $media,
        int $fileSize,
        string $fileExtension,
        Context $context
    ): void {
        $mimeType = \mime_content_type($filePath);
        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
        $mediaId = $media['media_id'];
        $fileName = \preg_replace('/[^a-zA-Z0-9_-]+/', '-', \mb_strtolower($media['file_name']));

        try {
            $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
        } catch (DuplicatedMediaFileNameException $e) {
            $this->fileSaver->persistFileToMedia(
                $mediaFile,
                $fileName . \mb_substr(Uuid::randomHex(), 0, 5),
                $mediaId,
                $context
            );
        } catch (IllegalFileNameException | EmptyMediaFilenameException $e) {
            $this->fileSaver->persistFileToMedia($mediaFile, Uuid::randomHex(), $mediaId, $context);
        }
    }

    private function setProcessedFlag(string $runId, Context $context, array $finishedUuids, array $failureUuids): void
    {
        $mediaFiles = $this->getMediaFiles($finishedUuids, $runId);
        $updateableMediaEntities = [];
        foreach ($mediaFiles as $mediaFile) {
            $mediaFileId = $mediaFile['id'];
            if (!isset($failureUuids[$mediaFileId])) {
                $updateableMediaEntities[] = [
                    'id' => $mediaFileId,
                    'processed' => true,
                ];
            }
        }

        if (empty($updateableMediaEntities)) {
            return;
        }

        $this->mediaFileRepo->update($updateableMediaEntities, $context);
    }
}
