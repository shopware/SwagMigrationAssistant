<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Media\Aggregate\MediaAlbum\MediaAlbumDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaAlbumTranslation\MediaAlbumTranslationDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperService;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class AssetConverter implements ConverterInterface
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
    }

    public function supports(): string
    {
        return MediaDefinition::getEntityName();
    }

    public function convert(array $data, Context $context): ConvertStruct
    {
        $locale = 'de_DE';

        $converted = [];
        $converted['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            MediaDefinition::getEntityName(),
            $data['id'],
            $context,
            [
                'uri' => $data['uri'],
                'file_size' => $data['file_size'],
            ]
        );
        unset($data['uri'], $data['file_size']);

        $translation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            MediaTranslationDefinition::getEntityName(),
            $data['id'] . ':' . $locale,
            $context
        );
        unset($data['id']);

        $this->helper->convertValue($translation, 'name', $data, 'name');
        $this->helper->convertValue($translation, 'description', $data, 'description');

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $locale, $context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $converted['translations'][$languageData['uuid']] = $translation;

        $newAlbum = [];
        $newAlbum['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            MediaAlbumDefinition::getEntityName(),
            $data['album']['id'],
            $context
        );

        $translation = [];
        $translation['id'] = $this->mappingService->createNewUuid(
            Shopware55Profile::PROFILE_NAME,
            MediaAlbumTranslationDefinition::getEntityName(),
            $data['album']['id'] . ':' . $locale,
            $context
        );
        unset($data['album']['id'], $data['albumID']);

        $this->helper->convertValue($translation, 'name', $data['album'], 'name');

        $languageData = $this->mappingService->getLanguageUuid(Shopware55Profile::PROFILE_NAME, $locale, $context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        $newAlbum['translations'][$languageData['uuid']] = $translation;

        $this->helper->convertValue($newAlbum, 'position', $data['album'], 'position', $this->helper::TYPE_INTEGER);
//            $this->helper->convertValue($newAlbum, 'createThumbnails', $asset['album']['settings'], 'create_thumbnails', $this->helper::TYPE_BOOLEAN);
        $newAlbum['createThumbnails'] = false; // TODO: Remove, needs a bugfix in the core
        $this->helper->convertValue($newAlbum, 'thumbnailSize', $data['album']['settings'], 'thumbnail_size');
        $this->helper->convertValue($newAlbum, 'icon', $data['album']['settings'], 'icon');
        $this->helper->convertValue($newAlbum, 'thumbnailHighDpi', $data['album']['settings'], 'thumbnail_high_dpi', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($newAlbum, 'thumbnailQuality', $data['album']['settings'], 'thumbnail_quality', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($newAlbum, 'thumbnailHighDpiQuality', $data['album']['settings'], 'thumbnail_high_dpi_quality', $this->helper::TYPE_INTEGER);

        $converted['album'] = $newAlbum;

        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['path'],
            $data['type'],
            $data['extension'],
            $data['file_size'],
            $data['width'],
            $data['height'],
            $data['userID'],
            $data['created'],
            $data['album']['settings']['id'],
            $data['album']['settings']['albumID']
        );

        return new ConvertStruct($converted, $data);
    }
}
