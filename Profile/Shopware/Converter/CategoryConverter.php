<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Exception\ParentEntityForChildNotFoundException;

abstract class CategoryConverter extends ShopwareConverter
{
    /**
     * @var MediaFileServiceInterface
     */
    protected $mediaFileService;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $oldCategoryId;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $runId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($mappingService, $loggingService);

        $this->mediaFileService = $mediaFileService;
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $checksum = $this->generateChecksum($data);
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->context = $context;
        $this->oldCategoryId = $data['id'];
        $this->runId = $migrationContext->getRunUuid();

        if (!isset($data['_locale'])) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $migrationContext->getRunUuid(),
                DefaultEntities::CATEGORY,
                $this->oldCategoryId,
                'locale'
            ));

            return new ConvertStruct(null, $data);
        }
        $this->locale = $data['_locale'];

        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['path'], // will be generated
            $data['left'],
            $data['right'],
            $data['added'],
            $data['changed'],
            $data['stream_id'],
            $data['metakeywords'],
            $data['metadescription'],
            $data['cmsheadline'],
            $data['meta_title'],
            $data['categorypath'],
            $data['shops'],

            // TODO check how to handle these
            $data['template'],
            $data['external_target'],
            $data['mediaID']
        );

        $cmsPageUuid = $this->mappingService->getDefaultCmsPageUuid($migrationContext->getConnection()->getId(), $context);
        if ($cmsPageUuid !== null) {
            $converted['cmsPageId'] = $cmsPageUuid;
        }

        if (isset($data['parent'])) {
            $parentMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $data['parent'],
                $this->context
            );

            if ($parentMapping === null) {
                throw new ParentEntityForChildNotFoundException(DefaultEntities::CATEGORY, $this->oldCategoryId);
            }
            $this->mappingIds[] = $parentMapping['id'];
            $converted['parentId'] = $parentMapping['entityUuid'];
            unset($parentMapping);
        // get last root category as previous sibling
        } elseif (!isset($data['previousSiblingId'])) {
            $previousSiblingUuid = $this->mappingService->getLowestRootCategoryUuid($context);

            if ($previousSiblingUuid !== null) {
                $converted['afterCategoryId'] = $previousSiblingUuid;
            }
        }
        unset($data['parent']);

        if (isset($data['previousSiblingId'])) {
            $previousSiblingMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $data['previousSiblingId'],
                $this->context
            );

            if ($previousSiblingMapping !== null) {
                $converted['afterCategoryId'] = $previousSiblingMapping['entityUuid'];
                $this->mappingIds[] = $previousSiblingMapping['id'];
            }
            unset($previousSiblingMapping);
        }
        unset($data['previousSiblingId'], $data['categoryPosition'], $previousSiblingMapping);

        $this->mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CATEGORY,
            $this->oldCategoryId,
            $this->context,
            $checksum
        );
        $converted['id'] = $this->mapping['entityUuid'];
        unset($data['id']);

        $this->convertValue($converted, 'description', $data, 'cmstext', self::TYPE_STRING);
        $this->convertValue($converted, 'level', $data, 'level', self::TYPE_INTEGER);
        $this->convertValue($converted, 'active', $data, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'isBlog', $data, 'blog', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'external', $data, 'external');
        $this->convertValue($converted, 'hideFilter', $data, 'hidefilter', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'hideTop', $data, 'hidetop', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'productBoxLayout', $data, 'product_box_layout');
        $this->convertValue($converted, 'hideSortings', $data, 'hide_sortings', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'sortingIds', $data, 'sorting_ids');
        $this->convertValue($converted, 'facetIds', $data, 'facet_ids');
        unset($data['position']);

        if (isset($data['asset'])) {
            $converted['media'] = $this->getCategoryMedia($data['asset']);
            unset($data['asset']);
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], $migrationContext->getDataSet()::getEntity(), $migrationContext->getConnection()->getName(), ['id', 'categoryID']);
        }
        unset($data['attributes']);

        $converted['translations'] = [];
        $this->setGivenCategoryTranslation($data, $converted);
        unset($data['_locale']);

        if (empty($data)) {
            $data = null;
        }

        $this->mapping['additionalData']['relatedMappings'] = $this->mappingIds;
        $this->mappingIds = [];
        $this->mappingService->updateMapping(
            $this->connectionId,
            DefaultEntities::CATEGORY,
            $this->mapping['oldIdentifier'],
            $this->mapping,
            $this->context
        );

        return new ConvertStruct($converted, $data, $this->mapping['id']);
    }

    protected function setGivenCategoryTranslation(array &$data, array &$converted): void
    {
        $originalData = $data;
        $this->convertValue($converted, 'name', $data, 'description');

        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $data['_locale']) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['categoryId'] = $converted['id'];

        $this->convertValue($localeTranslation, 'name', $originalData, 'description');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CATEGORY_TRANSLATION,
            $this->oldCategoryId . ':' . $data['_locale'],
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        try {
            $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);
        } catch (\Exception $exception) {
            $this->mappingService->deleteMapping($converted['id'], $this->connectionId, $this->context);
            throw $exception;
        }

        $localeTranslation['languageId'] = $languageUuid;

        if (isset($converted['customFields'])) {
            $localeTranslation['customFields'] = $converted['customFields'];
        }

        $converted['translations'][$languageUuid] = $localeTranslation;
    }

    protected function getCategoryMedia(array $media): array
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $media['id'],
            $this->context
        );
        $categoryMedia['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        if (empty($media['name'])) {
            $media['name'] = $categoryMedia['id'];
        }

        $this->getMediaTranslation($categoryMedia, ['media' => $media]);

        $albumMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $media['albumID'],
            $this->context
        );

        if ($albumMapping !== null) {
            $categoryMedia['mediaFolderId'] = $albumMapping['entityUuid'];
            $this->mappingIds[] = $albumMapping['id'];
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'entity' => MediaDataSet::getEntity(),
                'uri' => $media['uri'] ?? $media['path'],
                'fileName' => $media['name'],
                'fileSize' => (int) $media['file_size'],
                'mediaId' => $categoryMedia['id'],
            ]
        );

        return $categoryMedia;
    }

    protected function getMediaTranslation(array &$media, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'name', $data['media'], 'name');
        $this->convertValue($localeTranslation, 'description', $data['media'], 'description');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_TRANSLATION,
            $data['media']['id'] . ':' . $this->locale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $media['translations'][$languageUuid] = $localeTranslation;
    }
}
