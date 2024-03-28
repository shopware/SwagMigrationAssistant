<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\MigrationException;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CategoryDataSet;

#[Package('services-settings')]
abstract class CategoryConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected string $connectionName;

    protected Context $context;

    protected string $oldCategoryId;

    protected string $locale;

    protected string $runId;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaUuids = [];
        foreach ($converted as $data) {
            if (!isset($data['media']['id'])) {
                continue;
            }

            $mediaUuids[] = $data['media']['id'];
        }

        return $mediaUuids;
    }

    /**
     * @throws MigrationException
     */
    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->oldCategoryId = $data['id'];
        $this->runId = $migrationContext->getRunUuid();
        $this->migrationContext = $migrationContext;

        $connection = $migrationContext->getConnection();
        $this->connectionId = '';
        $this->connectionName = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
            $this->connectionName = $connection->getName();
        }

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
        $converted = [];

        $cmsPageUuid = $this->mappingService->getDefaultCmsPageUuid($this->connectionId, $context);
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
                throw MigrationException::parentEntityForChildNotFound(DefaultEntities::CATEGORY, $this->oldCategoryId);
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
        }
        unset($data['previousSiblingId'], $data['categoryPosition'], $previousSiblingMapping);

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::CATEGORY,
            $this->oldCategoryId,
            $this->context,
            $this->checksum
        );

        $converted['id'] = $this->mainMapping['entityUuid'];
        unset($data['id']);

        $this->convertValue($converted, 'description', $data, 'cmstext', self::TYPE_STRING);
        $this->convertValue($converted, 'level', $data, 'level', self::TYPE_INTEGER);
        $this->convertValue($converted, 'active', $data, 'active', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'externalLink', $data, 'external');
        $this->convertValue($converted, 'visible', $data, 'hidetop', self::TYPE_INVERT_BOOLEAN);
        $this->convertValue($converted, 'metaTitle', $data, 'meta_title');
        $this->convertValue($converted, 'metaDescription', $data, 'metadescription');
        $this->convertValue($converted, 'keywords', $data, 'metakeywords');

        if (!empty($converted['externalLink'])) {
            $converted['type'] = CategoryDefinition::TYPE_LINK;
        }
        if (isset($converted['metaDescription'])) {
            // meta description has a limit of 255 characters in SW6
            $converted['metaDescription'] = \mb_substr($converted['metaDescription'], 0, 255);
        }
        if (isset($converted['keywords'])) {
            // keywords has a limit of 255 characters in SW6
            $converted['keywords'] = \mb_substr($converted['keywords'], 0, 255);
        }

        if (isset($data['asset'])) {
            $converted['media'] = $this->getCategoryMedia($data['asset']);
            unset($data['asset']);
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes(
                $data['attributes'],
                $this->getDataSetEntity($migrationContext) ?? CategoryDataSet::getEntity(),
                $this->connectionName,
                ['id', 'categoryID'],
                $this->context
            );
        }
        unset($data['attributes']);

        $converted['translations'] = [];
        $this->setGivenCategoryTranslation($data, $converted);

        if ($converted['translations'] === []) {
            unset($converted['translations']);
        }

        unset(
            $data['position'],
            $data['blog'],
            $data['product_box_layout'],
            $data['hide_sortings'],
            $data['hidefilter'],
            $data['sorting_ids'],
            $data['facet_ids'],
            $data['path'], // will be generated
            $data['left'],
            $data['right'],
            $data['added'],
            $data['changed'],
            $data['stream_id'],
            $data['cmsheadline'],
            $data['categorypath'],
            $data['shops'],
            $data['template'],
            $data['external_target'],
            $data['mediaID'],
            $data['_locale']
        );

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $converted
     */
    protected function setGivenCategoryTranslation(array &$data, array &$converted): void
    {
        $originalData = $data;
        $this->convertValue($converted, 'name', $data, 'description');

        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $data['_locale']) {
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
        } catch (\Throwable $exception) {
            $this->mappingService->deleteMapping($converted['id'], $this->connectionId, $this->context);

            throw $exception;
        }

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;

            if (isset($converted['customFields'])) {
                $localeTranslation['customFields'] = $converted['customFields'];
            }

            $converted['translations'][$languageUuid] = $localeTranslation;
        }
    }

    /**
     * @param array<string, mixed> $media
     *
     * @return array<string, mixed>
     */
    protected function getCategoryMedia(array $media): array
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $media['id'],
            $this->context
        );

        $categoryMedia = [];
        $categoryMedia['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        if (empty($media['name'])) {
            $media['name'] = $categoryMedia['id'];
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

        $this->addMediaTranslation($categoryMedia, ['media' => $media]);
        $this->convertValue($categoryMedia, 'title', $media, 'name');
        $this->convertValue($categoryMedia, 'alt', $media, 'description');

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

        return $categoryMedia;
    }

    /**
     * @param array<string, mixed> $media
     * @param array<string, mixed> $data
     */
    protected function addMediaTranslation(array &$media, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'title', $data['media'], 'name');
        $this->convertValue($localeTranslation, 'alt', $data['media'], 'description');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_TRANSLATION,
            $data['media']['id'] . ':' . $this->locale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $media['translations'][$languageUuid] = $localeTranslation;
        }
    }
}
