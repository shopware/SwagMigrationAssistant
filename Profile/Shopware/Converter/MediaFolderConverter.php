<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class MediaFolderConverter extends ShopwareConverter
{
    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var string
     */
    protected $mainLocale;

    /**
     * @var MigrationContextInterface
     */
    protected $migrationContext;

    /**
     * @var string
     */
    protected $oldId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    /**
     * Converts the given data into the internal structure
     */
    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $checksum = $this->generateChecksum($data);
        $this->migrationContext = $migrationContext;
        $this->context = $context;
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->mainLocale = $data['_locale'];
        $this->oldId = $data['id'];

        unset($data['_locale']);

        $converted = [];
        $this->mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $data['id'],
            $this->context,
            $checksum
        );
        $converted['id'] = $this->mapping['entityUuid'];
        unset($data['id']);

        $defaultFolderId = $this->getDefaultFolderId();
        if ($defaultFolderId !== null) {
            $converted['parentId'] = $defaultFolderId;
        }

        if (isset($data['parentID'])) {
            $parentMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::MEDIA_FOLDER,
                $data['parentID'],
                $this->context
            );

            if ($parentMapping !== null) {
                $converted['parentId'] = $parentMapping['entityUuid'];
                $this->mappingIds[] = $parentMapping['id'];
            }
            unset($parentMapping);
        }
        unset($data['parentID']);

        if (!isset($converted['parentId'])) {
            $parentMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::MEDIA_FOLDER,
                'default_migration_media_folder',
                $this->context
            );
            $this->mappingIds[] = $parentMapping['id'];
            $configurationMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::MEDIA_FOLDER_CONFIGURATION,
                'default_migration_media_folder',
                $this->context
            );
            $this->mappingIds[] = $configurationMapping['id'];

            $converted['parent'] = [
                'id' => $parentMapping['entityUuid'],
                'name' => 'Migration media folder',
                'configuration' => [
                    'id' => $configurationMapping['entityUuid'],
                ],
            ];
        }

        $this->convertValue($converted, 'name', $data, 'name');

        if (isset($data['setting'])) {
            $converted['configuration'] = $this->getConfiguration($data['setting']);
            unset($data['setting']);
        } else {
            $converted['useParentConfiguration'] = true;
            // will immediately be overriden by MediaConfigIndexer
            $converted['configuration'] = [
                'id' => Uuid::randomHex(),
            ];
        }

        unset($data['position'], $data['garbage_collectable']);

        if (empty($data)) {
            $data = null;
        }

        $this->mapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];
        $this->mappingService->updateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $this->mapping['oldIdentifier'],
            $this->mapping,
            $this->context
        );

        return new ConvertStruct($converted, $data, $this->mapping['id']);
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    protected function getConfiguration(array &$setting): array
    {
        $configuration = [];
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER_CONFIGURATION,
            $setting['id'],
            $this->context
        );
        $configuration['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $this->convertValue($configuration, 'createThumbnails', $setting, 'create_thumbnails', self::TYPE_BOOLEAN);
        $this->convertValue($configuration, 'thumbnailQuality', $setting, 'thumbnail_quality', self::TYPE_INTEGER);

        if (isset($setting['thumbnail_size']) && !empty($setting['thumbnail_size'])) {
            $thumbnailSizes = explode(';', $setting['thumbnail_size']);

            $configuration['mediaThumbnailSizes'] = [];
            foreach ($thumbnailSizes as $size) {
                $currentSize = explode('x', $size);
                $thumbnailSize['width'] = (int) $currentSize[0];
                $thumbnailSize['height'] = (int) $currentSize[1];

                $uuid = $this->mappingService->getThumbnailSizeUuid(
                    $thumbnailSize['width'],
                    $thumbnailSize['height'],
                    $this->migrationContext,
                    $this->context
                );

                if ($uuid === null) {
                    $mapping = $this->mappingService->getOrCreateMapping(
                        $this->connectionId,
                        DefaultEntities::MEDIA_THUMBNAIL_SIZE,
                        $thumbnailSize['width'] . '-' . $thumbnailSize['height'],
                        $this->context
                    );
                    $uuid = $mapping['entityUuid'];
                    $this->mappingIds[] = $mapping['id'];
                }

                $thumbnailSize['id'] = $uuid;
                $configuration['mediaThumbnailSizes'][] = $thumbnailSize;
            }
        }

        return $configuration;
    }

    protected function getDefaultFolderId(): ?string
    {
        switch ($this->oldId) {
            case '1':
            case '-12':
                return $this->mappingService->getDefaultFolderIdByEntity(DefaultEntities::PRODUCT_MANUFACTURER, $this->migrationContext, $this->context);
            case '-5':
                return $this->mappingService->getDefaultFolderIdByEntity(DefaultEntities::MAIL_TEMPLATE, $this->migrationContext, $this->context);
            case '-1':
                return $this->mappingService->getDefaultFolderIdByEntity(DefaultEntities::PRODUCT, $this->migrationContext, $this->context);
        }

        return null;
    }
}
