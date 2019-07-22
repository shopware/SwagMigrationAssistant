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
     * @var MappingServiceInterface
     */
    protected $mappingService;

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
     * @var LoggingServiceInterface
     */
    protected $loggingService;

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
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
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
            $parentUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $data['parent'],
                $this->context
            );

            if ($parentUuid === null) {
                throw new ParentEntityForChildNotFoundException(DefaultEntities::CATEGORY, $this->oldCategoryId);
            }

            $converted['parentId'] = $parentUuid;
        // get last root category as previous sibling
        } elseif (!isset($data['previousSiblingId'])) {
            $previousSiblingUuid = $this->mappingService->getLowestRootCategoryUuid($context);

            if ($previousSiblingUuid !== null) {
                $converted['afterCategoryId'] = $previousSiblingUuid;
            }
        }
        unset($data['parent']);

        if (isset($data['previousSiblingId'])) {
            $previousSiblingUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $data['previousSiblingId'],
                $this->context
            );

            $converted['afterCategoryId'] = $previousSiblingUuid;
        }
        unset($data['previousSiblingId']);

        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::CATEGORY,
            $this->oldCategoryId,
            $this->context
        );
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
            $converted['customFields'] = $this->getAttributes($data['attributes']);
        }
        unset($data['attributes']);

        $converted['translations'] = [];
        $this->setGivenCategoryTranslation($data, $converted);
        unset($data['_locale']);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
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

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::CATEGORY_TRANSLATION,
            $this->oldCategoryId . ':' . $data['_locale'],
            $this->context
        );

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

    protected function getAttributes(array $attributes): array
    {
        $result = [];

        foreach ($attributes as $attribute => $value) {
            if ($attribute === 'id' || $attribute === 'categoryID') {
                continue;
            }
            $result[DefaultEntities::CATEGORY . '_' . $attribute] = $value;
        }

        return $result;
    }

    protected function getCategoryMedia(array $media): array
    {
        $categoryMedia['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $media['id'],
            $this->context
        );

        if (empty($media['name'])) {
            $media['name'] = $categoryMedia['id'];
        }

        $this->getMediaTranslation($categoryMedia, ['media' => $media]);

        $albumUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $media['albumID'],
            $this->context
        );

        if ($albumUuid !== null) {
            $categoryMedia['mediaFolderId'] = $albumUuid;
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

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA_TRANSLATION,
            $data['media']['id'] . ':' . $this->locale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $media['translations'][$languageUuid] = $localeTranslation;
    }
}
