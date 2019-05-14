<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class MediaFolderConverter extends Shopware55Converter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var string
     */
    private $mainLocale;

    /**
     * @var MigrationContextInterface
     */
    private $migrationContext;

    /**
     * @var string
     */
    private $oldId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return DefaultEntities::MEDIA_FOLDER;
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    /**
     * Converts the given data into the internal structure
     */
    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->migrationContext = $migrationContext;
        $this->context = $context;
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->mainLocale = $data['_locale'];
        $this->oldId = $data['id'];

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $data['id'],
            $this->context
        );
        unset($data['id']);

        $defaultFolderId = $this->getDefaultFolderId();
        if ($defaultFolderId !== null) {
            $converted['parentId'] = $defaultFolderId;
        }

        if (isset($data['parentID'])) {
            $parentUuid = $this->mappingService->getUuid(
              $this->connectionId,
                DefaultEntities::MEDIA_FOLDER,
              $data['parentID'],
              $this->context
            );

            if ($parentUuid !== null) {
                $converted['parentId'] = $parentUuid;
            }
        }

        if (!isset($converted['parentId'])) {
            $converted['parent'] = [
                'id' => $this->mappingService->createNewUuid(
                  $this->connectionId,
                    DefaultEntities::MEDIA_FOLDER,
                  'default_migration_media_folder',
                  $this->context
                ),

                'name' => 'Migration media folder',
                'configuration' => [
                    'id' => $this->mappingService->createNewUuid(
                      $this->connectionId,
                      DefaultEntities::MEDIA_FOLDER_CONFIGURATION,
                        'default_migration_media_folder',
                        $this->context
                    ),
                ],
            ];
        }

        $this->convertValue($converted, 'name', $data, 'name');

        if (isset($data['setting'])) {
            $converted['configuration'] = $this->getConfiguration($data['setting']);
            unset($setting);
        } else {
            $converted['useParentConfiguration'] = true;
            // will immediately be overriden by MediaConfigIndexer
            $converted['configuration'] = [
                'id' => Uuid::randomHex(),
            ];
        }

        return new ConvertStruct($converted, $data);
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    private function getConfiguration(array &$setting): array
    {
        $configuration = [];
        $configuration['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER_CONFIGURATION,
            $setting['id'],
            $this->context
        );

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
                    $uuid = $this->mappingService->createNewUuid(
                        $this->connectionId,
                        DefaultEntities::MEDIA_THUMBNAIL_SIZE,
                        $thumbnailSize['width'] . '' . $thumbnailSize['height'],
                        $this->context
                    );
                }

                $thumbnailSize['id'] = $uuid;
                $configuration['mediaThumbnailSizes'][] = $thumbnailSize;
            }
        }

        return $configuration;
    }

    private function getDefaultFolderId(): ?string
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
