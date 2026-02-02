<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Media;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Media\MediaException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\MediaFileMissingLog;
use SwagMigrationAssistant\Migration\Logging\Log\MediaMimeTypeUnknownLog;
use SwagMigrationAssistant\Migration\Logging\Log\MediaTemporaryFileFailedLog;
use SwagMigrationAssistant\Migration\Logging\Log\RunExceptionLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\Media\Processor\BaseMediaService;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\Media\Strategy\StrategyResolverInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class LocalMediaProcessor extends BaseMediaService implements MediaFileProcessorInterface
{
    /**
     * @param EntityRepository<SwagMigrationMediaFileCollection> $mediaFileRepo
     * @param StrategyResolverInterface[] $resolver
     */
    public function __construct(
        EntityRepository $mediaFileRepo,
        private readonly FileSaver $fileSaver,
        private readonly LoggingServiceInterface $loggingService,
        private readonly iterable $resolver,
        Connection $dbalConnection,
    ) {
        parent::__construct($dbalConnection, $mediaFileRepo);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === MediaDataSet::getEntity();
    }

    public function process(MigrationContextInterface $migrationContext, Context $context, array $workload): array
    {
        $mappedWorkload = [];
        foreach ($workload as $work) {
            $mappedWorkload[$work->getMediaId()] = $work;
        }

        $media = $this->getMediaFiles(\array_keys($mappedWorkload), $migrationContext->getRunUuid());
        $mappedWorkload = $this->getMediaPathMapping($media, $mappedWorkload, $migrationContext);

        return $this->copyMediaFiles($media, $mappedWorkload, $migrationContext, $context);
    }

    /**
     * @param list<array<string, mixed>> $media
     * @param array<MediaProcessWorkloadStruct> $mappedWorkload
     *
     * @return array<MediaProcessWorkloadStruct>
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

    /**
     * @param array<string, mixed> $mediaFile
     */
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
     * @param list<array<string, mixed>> $media
     * @param array<MediaProcessWorkloadStruct> $mappedWorkload
     *
     * @return MediaProcessWorkloadStruct[]
     */
    private function copyMediaFiles(
        array $media,
        array $mappedWorkload,
        MigrationContextInterface $migrationContext,
        Context $context,
    ): array {
        $processedMedia = [];
        $failedMedia = [];

        foreach ($media as $mediaFile) {
            $mediaId = $mediaFile['media_id'];
            $sourcePath = $mappedWorkload[$mediaId]->getAdditionalData()['path'];

            if (!\is_file($sourcePath)) {
                $resolver = $this->getResolver($mediaFile, $migrationContext);

                if ($resolver === null) {
                    $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $this->loggingService->log(
                        MigrationLogBuilder::fromMigrationContext($migrationContext)
                            ->withEntityName(MediaDefinition::ENTITY_NAME)
                            ->withSourceData([
                                'media_id' => $mediaId,
                                'source_path' => $sourcePath,
                                'media' => $mediaFile,
                            ])
                            ->withEntityId($mediaId)
                            ->build(MediaFileMissingLog::class)
                    );
                    $processedMedia[] = $mediaId;
                    $failedMedia[] = $mediaId;

                    continue;
                }
            }

            $fileExtension = \pathinfo($sourcePath, \PATHINFO_EXTENSION);
            $filePath = \tempnam(\sys_get_temp_dir(), 'SwagMigrationAssistant-');
            if ($filePath === false) {
                $failedMedia[] = $mediaId;
                $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);

                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($migrationContext)
                        ->withEntityName(MediaDefinition::ENTITY_NAME)
                        ->withSourceData([
                            'media_id' => $mediaId,
                            'source_path' => $sourcePath,
                            'media' => $mediaFile,
                        ])
                        ->withEntityId($mediaId)
                        ->build(MediaTemporaryFileFailedLog::class)
                );

                continue;
            }

            if (\copy($sourcePath, $filePath)) {
                $fileSize = (int) \filesize($filePath);
                $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::FINISH_STATE);

                try {
                    $this->persistFileToMedia(
                        $migrationContext,
                        $filePath,
                        $mediaFile,
                        $fileSize,
                        $fileExtension,
                        $mappedWorkload,
                        $failedMedia,
                        $context
                    );

                    $processedMedia[] = $mediaId;
                } catch (\Exception $e) {
                    $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                    $failedMedia[] = $mediaId;

                    $this->loggingService->log(
                        MigrationLogBuilder::fromMigrationContext($migrationContext)
                            ->withEntityName(MediaDefinition::ENTITY_NAME)
                            ->withSourceData([
                                'media_id' => $mediaId,
                                'source_path' => $sourcePath,
                                'media' => $mediaFile,
                            ])
                            ->withExceptionMessage($e->getMessage())
                            ->withExceptionTrace($e->getTrace())
                            ->withEntityId($mediaId)
                            ->build(RunExceptionLog::class)
                    );
                }
                \unlink($filePath);
            } else {
                $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);
                $this->loggingService->log(
                    MigrationLogBuilder::fromMigrationContext($migrationContext)
                        ->withEntityName(MediaDefinition::ENTITY_NAME)
                        ->withSourceData([
                            'media_id' => $mediaId,
                            'source_path' => $sourcePath,
                            'media' => $mediaFile,
                        ])
                        ->withEntityId($mediaId)
                        ->build(MediaFileMissingLog::class)
                );
                $failedMedia[] = $mediaId;
            }
        }

        $this->setProcessedFlag($migrationContext->getRunUuid(), $context, $processedMedia, $failedMedia);

        return \array_values($mappedWorkload);
    }

    /**
     * @param array<string, mixed> $media
     * @param array<MediaProcessWorkloadStruct> $mappedWorkload
     * @param list<mixed> $failedMedia
     */
    private function persistFileToMedia(
        MigrationContextInterface $migrationContext,
        string $filePath,
        array $media,
        int $fileSize,
        string $fileExtension,
        array $mappedWorkload,
        array &$failedMedia,
        Context $context,
    ): void {
        $mediaId = $media['media_id'];
        $mimeType = \mime_content_type($filePath);

        if ($mimeType === false) {
            $failedMedia[] = $mediaId;
            $mappedWorkload[$mediaId]->setState(MediaProcessWorkloadStruct::ERROR_STATE);

            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(MediaDefinition::ENTITY_NAME)
                    ->withSourceData([
                        'media_id' => $mediaId,
                        'file_path' => $filePath,
                        'media' => $media,
                    ])
                    ->withEntityId($mediaId)
                    ->build(MediaMimeTypeUnknownLog::class)
            );

            return;
        }

        $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);

        $fileName = (string) \preg_replace('/[^a-zA-Z0-9_-]+/', '-', \mb_strtolower($media['file_name']));

        try {
            $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
        } catch (MediaException $mediaException) {
            if ($mediaException->getErrorCode() === MediaException::MEDIA_DUPLICATED_FILE_NAME) {
                $this->fileSaver->persistFileToMedia(
                    $mediaFile,
                    $fileName . \mb_substr(Uuid::randomHex(), 0, 5),
                    $mediaId,
                    $context
                );
            } elseif (\in_array($mediaException->getErrorCode(), [MediaException::MEDIA_ILLEGAL_FILE_NAME, MediaException::MEDIA_EMPTY_FILE_NAME], true)) {
                $this->fileSaver->persistFileToMedia($mediaFile, Uuid::randomHex(), $mediaId, $context);
            }
        }
    }
}
