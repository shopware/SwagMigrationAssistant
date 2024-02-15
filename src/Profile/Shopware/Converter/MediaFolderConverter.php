<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class MediaFolderConverter extends ShopwareConverter
{
    protected Context $context;

    protected string $connectionId;

    protected string $mainLocale;

    protected string $oldId;

    /**
     * Converts the given data into the internal structure
     */
    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->mainLocale = $data['_locale'];
        $this->oldId = $data['id'];
        unset($data['_locale']);

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
        }

        $converted = [];
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $data['id'],
            $this->context,
            $this->checksum
        );
        $converted['id'] = $this->mainMapping['entityUuid'];
        unset($data['id']);

        $defaultFolderId = $this->getDefaultFolderId($migrationContext);
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
            $converted['configuration'] = $this->getConfiguration($data['setting'], $migrationContext);
            $converted['useParentConfiguration'] = false;
            unset($data['setting']);
        } else {
            $converted['useParentConfiguration'] = true;
            // will immediately be overridden by MediaConfigIndexer
            $converted['configuration'] = [
                'id' => Uuid::randomHex(),
            ];
        }

        unset($data['position'], $data['garbage_collectable']);

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    protected function getConfiguration(array &$setting, MigrationContextInterface $migrationContext): array
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
            $thumbnailSizes = \explode(';', \mb_strtolower($setting['thumbnail_size']));

            $configuration['mediaThumbnailSizes'] = [];
            foreach ($thumbnailSizes as $size) {
                $currentSize = \explode('x', $size);

                $thumbnailSize = [];
                $thumbnailSize['width'] = (int) $currentSize[0];
                $thumbnailSize['height'] = (int) $currentSize[1];

                $uuid = $this->mappingService->getThumbnailSizeUuid(
                    $thumbnailSize['width'],
                    $thumbnailSize['height'],
                    $migrationContext,
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

    protected function getDefaultFolderId(MigrationContextInterface $migrationContext): ?string
    {
        switch ($this->oldId) {
            case '1':
            case '-12':
                return $this->mappingService->getDefaultFolderIdByEntity(DefaultEntities::PRODUCT_MANUFACTURER, $migrationContext, $this->context);
            case '-5':
                return $this->mappingService->getDefaultFolderIdByEntity(DefaultEntities::MAIL_TEMPLATE, $migrationContext, $this->context);
            case '-1':
                return $this->mappingService->getDefaultFolderIdByEntity(DefaultEntities::PRODUCT, $migrationContext, $this->context);
        }

        return null;
    }
}
