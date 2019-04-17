<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductProperty\ProductPropertyDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class PropertyGroupOptionConverter extends AbstractConverter
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    /**
     * @var string
     */
    private $locale;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->loggingService = $loggingService;
        $this->mediaFileService = $mediaFileService;
    }

    public function getSupportedEntityName(): string
    {
        return PropertyGroupOptionDefinition::getEntityName();
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
    }

    public function writeMapping(Context $context): void
    {
        $this->mappingService->writeMapping($context);
    }

    public function convert(array $data, Context $context, MigrationContextInterface $migrationContext): ConvertStruct
    {
        $this->context = $context;
        $this->locale = $data['_locale'];
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();

        $converted = [
            'id' => $this->mappingService->createNewUuid(
                $this->connectionId,
                PropertyGroupOptionDefinition::getEntityName(),
                hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
                $context
            ),

            'group' => [
                'id' => $this->mappingService->createNewUuid(
                    $this->connectionId,
                    PropertyGroupDefinition::getEntityName(),
                    hash('md5', strtolower($data['group']['name'])),
                    $context
                ),
            ],
        ];

        $this->createAndDeleteNecessaryMappings($data, $converted);

        if (isset($data['media'])) {
            $this->getMedia($converted, $data);
        }

        $this->getConfiguratorSettings($data, $converted);
        $this->getProperties($data, $converted);
        $this->getTranslation($data, $converted);

        return new ConvertStruct($converted, null);
    }

    private function getMedia(array &$converted, array $data): void
    {
        if (!isset($data['media']['id'])) {
            $this->loggingService->addInfo(
                $this->runId,
                Shopware55LogTypes::PROPERTY_MEDIA_NOT_CONVERTED,
                'Property-Group-Option-Media could not be converted',
                'Property-Group-Option-Media could not be converted.',
                [
                    'uuid' => $converted['id'],
                    'id' => $data['id'],
                ]
            );

            return;
        }

        $newMedia = [];
        $newMedia['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            MediaDefinition::getEntityName(),
            $data['media']['id'],
            $this->context
        );

        if (!isset($data['media']['name'])) {
            $data['media']['name'] = $newMedia['id'];
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'uri' => $data['media']['uri'] ?? $data['media']['path'],
                'fileName' => $data['media']['name'],
                'fileSize' => (int) $data['media']['file_size'],
                'mediaId' => $newMedia['id'],
            ]
        );

        $this->getMediaTranslation($newMedia, $data);
        $this->helper->convertValue($newMedia, 'name', $data['media'], 'name');
        $this->helper->convertValue($newMedia, 'description', $data['media'], 'description');

        $converted['media'] = $newMedia;
    }

    // Todo: Check if this is necessary, because name and description is currently not translatable
    private function getMediaTranslation(array &$media, array $data): void
    {
        $languageData = $this->mappingService->getDefaultLanguageUuid($this->context);
        if ($languageData['createData']['localeCode'] === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->helper->convertValue($localeTranslation, 'name', $data['media'], 'name');
        $this->helper->convertValue($localeTranslation, 'description', $data['media'], 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            MediaTranslationDefinition::getEntityName(),
            $data['media']['id'] . ':' . $this->locale,
            $this->context
        );

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $localeTranslation['language']['id'] = $languageData['uuid'];
            $localeTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $localeTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $localeTranslation['languageId'] = $languageData['uuid'];
        }

        $media['translations'][$languageData['uuid']] = $localeTranslation;
    }

    private function getConfiguratorSettings(array &$data, array &$converted): void
    {
        $variantOptionsToProductContainer = $this->mappingService->getUuidList(
            $this->connectionId,
            'main_product_options',
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
            $this->context
        );

        foreach ($variantOptionsToProductContainer as $uuid) {
            $converted['productConfiguratorSettings'][] = [
                'id' => $this->mappingService->createNewUuid(
                    $this->connectionId,
                    ProductPropertyDefinition::getEntityName(),
                    $data['id'] . '_' . $uuid,
                    $this->context
                ),

                'productId' => $uuid,
            ];
        }
    }

    private function getProperties(array $data, array &$converted): void
    {
        $propertyOptionsToProductContainer = $this->mappingService->getUuidList(
            $this->connectionId,
            'main_product_filter',
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])),
            $this->context
        );

        foreach ($propertyOptionsToProductContainer as $uuid) {
            $converted['productProperties'][] = [
                'id' => $uuid,
            ];
        }
    }

    private function createAndDeleteNecessaryMappings(array $data, array $converted): void
    {
        $this->mappingService->createNewUuid(
            $this->connectionId,
            PropertyGroupOptionDefinition::getEntityName() . '_' . $data['type'],
            $data['id'],
            $this->context,
            null,
            $converted['id']
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            PropertyGroupDefinition::getEntityName() . '_' . $data['type'],
            $data['group']['id'],
            $this->context,
            null,
            $converted['group']['id']
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            PropertyGroupOptionDefinition::getEntityName(),
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'] . '_' . $data['type'])),
            $this->context
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            PropertyGroupOptionDefinition::getEntityName(),
            hash('md5', strtolower($data['group']['name'] . '_' . $data['type'])),
            $this->context
        );

        if ($data['type'] === 'option') {
            $propertyOptionMapping = $this->mappingService->getUuid(
                $this->connectionId,
                PropertyGroupOptionDefinition::getEntityName(),
                hash('md5', strtolower($data['name'] . '_' . $data['group']['name'] . '_property')),
                $this->context
            );

            $propertyGroupMapping = $this->mappingService->getUuid(
                $this->connectionId,
                PropertyGroupDefinition::getEntityName(),
                hash('md5', strtolower($data['group']['name'] . '_property')),
                $this->context
            );

            if ($propertyOptionMapping !== null) {
                $this->mappingService->deleteMapping(
                    $propertyOptionMapping,
                    $this->connectionId,
                    $this->context
                );
            }

            if ($propertyGroupMapping !== null) {
                $this->mappingService->deleteMapping(
                    $propertyGroupMapping,
                    $this->connectionId,
                    $this->context
                );
            }
        }
    }

    private function getTranslation(array &$data, array &$converted): void
    {
        $languageData = $this->mappingService->getDefaultLanguageUuid($this->context);
        $defaultLanguageUuid = $languageData['uuid'];

        $converted['translations'][$defaultLanguageUuid] = [];
        $this->helper->convertValue($converted['translations'][$defaultLanguageUuid], 'name', $data, 'name', $this->helper::TYPE_STRING);
        $this->helper->convertValue($converted['translations'][$defaultLanguageUuid], 'position', $data, 'position', $this->helper::TYPE_INTEGER);

        $converted['group']['translations'][$defaultLanguageUuid] = [];
        $this->helper->convertValue($converted['group']['translations'][$defaultLanguageUuid], 'name', $data['group'], 'name', $this->helper::TYPE_STRING);
        $this->helper->convertValue($converted['group']['translations'][$defaultLanguageUuid], 'description', $data['group'], 'description', $this->helper::TYPE_STRING);
    }
}
