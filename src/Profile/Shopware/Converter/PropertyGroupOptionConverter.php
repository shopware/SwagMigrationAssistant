<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\Builder\MigrationLogBuilder;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertChildEntityFailedLog;
use SwagMigrationAssistant\Migration\Logging\Log\ConvertSourceDataIncompleteLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\LanguageLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;

#[Package('fundamentals@after-sales')]
abstract class PropertyGroupOptionConverter extends ShopwareConverter
{
    protected string $connectionId;

    protected Context $context;

    protected string $runId;

    protected string $locale;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected MediaFileServiceInterface $mediaFileService,
        protected readonly LanguageLookup $languageLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function getSourceIdentifier(array $data): string
    {
        $group = 'unknown';
        if (isset($data['group'])) {
            $group = $data['group']['name'];
        }

        return Hasher::hash(\mb_strtolower($data['name'] . '_' . $group . '_' . $data['type']), 'md5');
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

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->locale = $data['_locale'];
        $this->runId = $migrationContext->getRunUuid();

        $connection = $migrationContext->getConnection();
        $this->connectionId = $connection->getId();

        if (!isset($data['group']['name'])) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($migrationContext)
                    ->withEntityName(PropertyGroupDefinition::ENTITY_NAME)
                    ->withFieldName('id')
                    ->withFieldSourcePath('group.name')
                    ->withSourceData($data)
                    ->build(ConvertSourceDataIncompleteLog::class)
            );

            return new ConvertStruct(null, $data);
        }

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            Hasher::hash(\mb_strtolower($data['name'] . '_' . $data['group']['name']), 'md5'),
            $context
        );

        $this->mappingIds[] = $mapping['id'];

        $propertyGroupMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP,
            Hasher::hash(\mb_strtolower($data['group']['name']), 'md5'),
            $context
        );

        $this->mappingIds[] = $propertyGroupMapping['id'];

        $converted = [
            'id' => $mapping['entityId'],
            'group' => [
                'id' => $propertyGroupMapping['entityId'],
            ],
        ];

        $this->createAndDeleteNecessaryMappings($data, $converted);

        if (isset($data['media'])) {
            $this->setMedia($converted, $data);
        }

        $this->setTranslation($data, $converted);
        $this->updateMainMapping($migrationContext, $context);

        $mainMapping = $this->mainMapping['id'] ?? null;

        return new ConvertStruct($converted, null, $mainMapping);
    }

    /**
     * @param array<mixed> $converted
     * @param array<mixed> $data
     */
    protected function setMedia(array &$converted, array $data): void
    {
        if (!isset($data['media']['id'])) {
            $this->loggingService->log(
                MigrationLogBuilder::fromMigrationContext($this->migrationContext)
                    ->withEntityName(PropertyGroupDefinition::ENTITY_NAME)
                    ->withFieldName('media.id')
                    ->withFieldSourcePath('media.id')
                    ->withSourceData($data)
                    ->build(ConvertChildEntityFailedLog::class)
            );

            return;
        }

        $newMedia = [];
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $data['media']['id'],
            $this->context
        );
        $newMedia['id'] = $mapping['entityId'];
        $this->mappingIds[] = $mapping['id'];

        if (empty($data['media']['name'])) {
            $data['media']['name'] = $newMedia['id'];
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'entity' => MediaDataSet::getEntity(),
                'uri' => $data['media']['uri'] ?? $data['media']['path'],
                'fileName' => $data['media']['name'],
                'fileSize' => (int) $data['media']['file_size'],
                'mediaId' => $newMedia['id'],
            ]
        );

        $this->setMediaTranslation($newMedia, $data);
        $this->convertValue($newMedia, 'title', $data['media'], 'name');
        $this->convertValue($newMedia, 'alt', $data['media'], 'description');

        $albumMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $data['media']['albumID'],
            $this->context
        );

        if ($albumMapping !== null) {
            $newMedia['mediaFolderId'] = $albumMapping['entityId'];
            $this->mappingIds[] = $albumMapping['id'];
        }

        $converted['media'] = $newMedia;
    }

    /**
     * @param array<mixed> $media
     * @param array<mixed> $data
     */
    protected function setMediaTranslation(array &$media, array $data): void
    {
        $language = $this->languageLookup->getLanguageEntity($this->context);
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
        $localeTranslation['id'] = $mapping['entityId'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->languageLookup->get($this->locale, $this->context);
        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $media['translations'][$languageUuid] = $localeTranslation;
        }
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed> $converted
     */
    protected function createAndDeleteNecessaryMappings(array $data, array $converted): void
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION . '_' . $data['type'],
            $data['id'],
            $this->context,
            null,
            null,
            $converted['id']
        );
        $this->mappingIds[] = $mapping['id'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP . '_' . $data['type'],
            $data['group']['id'],
            $this->context,
            null,
            null,
            $converted['group']['id']
        );
        $this->mappingIds[] = $mapping['id'];

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            Hasher::hash(\mb_strtolower($data['name'] . '_' . $data['group']['name'] . '_' . $data['type']), 'md5'),
            $this->context,
            $this->checksum
        );

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION,
            Hasher::hash(\mb_strtolower($data['group']['name'] . '_' . $data['type']), 'md5'),
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed> $converted
     */
    protected function setTranslation(array &$data, array &$converted): void
    {
        $language = $this->languageLookup->getLanguageEntity($this->context);
        if ($language === null) {
            return;
        }

        $defaultLanguageUuid = $language->getId();

        if (isset($data['name']) && $data['name'] !== '') {
            $converted['translations'][$defaultLanguageUuid] = [];
            $this->convertValue($converted['translations'][$defaultLanguageUuid], 'name', $data, 'name', self::TYPE_STRING);
            $this->convertValue($converted['translations'][$defaultLanguageUuid], 'position', $data, 'position', self::TYPE_INTEGER);
        }

        if (isset($data['group']['name']) && $data['group']['name'] !== '') {
            $converted['group']['translations'][$defaultLanguageUuid] = [];
            $this->convertValue($converted['group']['translations'][$defaultLanguageUuid], 'name', $data['group'], 'name', self::TYPE_STRING);
            $this->convertValue($converted['group']['translations'][$defaultLanguageUuid], 'description', $data['group'], 'description', self::TYPE_STRING);
        }
    }
}
