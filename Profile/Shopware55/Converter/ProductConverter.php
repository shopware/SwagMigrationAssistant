<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Media\Aggregate\MediaTranslation\MediaTranslationDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Tax\TaxDefinition;
use Shopware\Core\System\Unit\Aggregate\UnitTranslation\UnitTranslationDefinition;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationNext\Migration\Converter\AbstractConverter;
use SwagMigrationNext\Migration\Converter\ConvertStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Profile\Shopware55\Exception\ParentEntityForChildNotFoundException;
use SwagMigrationNext\Profile\Shopware55\Logging\Shopware55LogTypes;
use SwagMigrationNext\Profile\Shopware55\Premapping\ProductManufacturerReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class ProductConverter extends AbstractConverter
{
    public const MAIN_PRODUCT_TYPE = 1;
    public const VARIANT_PRODUCT_TYPE = 2;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperService
     */
    private $helper;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $oldProductId;

    /**
     * @var string
     */
    private $connectionId;

    /**
     * @var string
     */
    private $runId;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var string[]
     */
    private $requiredDataFieldKeys = [
        'tax',
        'prices',
    ];

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperService $converterHelperService,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
    }

    public function getSupportedEntityName(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function getSupportedProfileName(): string
    {
        return Shopware55Profile::PROFILE_NAME;
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
        $this->context = $context;
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->oldProductId = $data['detail']['ordernumber'];
        $locale = $data['_locale'];

        $fields = $this->helper->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);
        if (!empty($fields)) {
            $this->loggingService->addWarning(
                $this->runId,
                Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                'Empty necessary data fields',
                sprintf('Product-Entity could not converted cause of empty necessary field(s): %s.', implode(', ', $fields)),
                [
                    'id' => $this->oldProductId,
                    'entity' => 'Product',
                    'fields' => $fields,
                ],
                \count($fields)
            );

            return new ConvertStruct(null, $data);
        }

        $productType = (int) $data['detail']['kind'];
        unset($data['detail']['kind']);
        $isProductWithVariant = $data['configurator_set_id'] !== null;

//        if ($productType === self::MAIN_PRODUCT_TYPE && $isProductWithVariant) {
//            return $this->convertMainProduct($data); TODO reimplement when variant handling is implemented in core
//        }

        if ($productType === self::VARIANT_PRODUCT_TYPE && $isProductWithVariant) {
            return new ConvertStruct(null, $data);
//            return $this->convertVariantProduct($data); TODO reimplement when variant handling is implemented in core
        }

        $converted = $this->getUuidForProduct($data);
        $converted = $this->getProductData($data, $converted);

        if (isset($data['manufacturer'])) {
            $converted['manufacturer'] = $this->getManufacturer($data['manufacturer'], $locale);
            unset($data['manufacturer'], $data['supplierID']);
        } else {
            $manufacturerUuid = $this->mappingService->getUuid(
                $this->connectionId,
                ProductManufacturerReader::getMappingName(),
                'default_manufacturer',
                $this->context
            );

            if ($manufacturerUuid !== null) {
                $converted['manufacturerId'] = $manufacturerUuid;

                unset($data['supplierID']);
            } else {
                $this->loggingService->addWarning(
                    $this->runId,
                    Shopware55LogTypes::EMPTY_NECESSARY_DATA_FIELDS,
                    'Empty necessary data fields',
                    'Product-Entity could not converted cause of empty necessary field(s): manufacturer.',
                    [
                        'id' => $this->oldProductId,
                        'entity' => ProductDefinition::getEntityName(),
                        'fields' => ['manufacturer'],
                    ],
                    1
                );

                return new ConvertStruct(null, $data);
            }
        }

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    private function convertMainProduct(array $data): ConvertStruct
    {
        $containerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            ProductDefinition::getEntityName() . '_container',
            $data['id'],
            $this->context
        );
        $converted['id'] = $containerUuid;
        unset($data['detail']['articleID']);

        $converted = $this->getProductData($data, $converted);

        $converted['children'][] = $converted;
        $converted['children'][0]['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            ProductDefinition::getEntityName(),
            $this->oldProductId,
            $this->context
        );
        $converted['children'][0]['parentId'] = $containerUuid;
        unset($data['detail']['id']);

        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    private function convertVariantProduct(array $data): ConvertStruct
    {
        $parentUuid = $this->mappingService->getUuid(
            $this->connectionId,
            ProductDefinition::getEntityName() . '_container',
            $data['detail']['articleID'],
            $this->context
        );

        if ($parentUuid === null) {
            throw new ParentEntityForChildNotFoundException(ProductDefinition::getEntityName(), $this->oldProductId);
        }

        $converted = $this->getUuidForProduct($data);
        $converted['parentId'] = $parentUuid;
        $converted = $this->getProductData($data, $converted);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    private function getUuidForProduct(array &$data): array
    {
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            ProductDefinition::getEntityName(),
            $this->oldProductId,
            $this->context
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            ProductDefinition::getEntityName() . '_mainProduct',
            $data['detail']['articleID'],
            $this->context,
            null,
            $converted['id']
        );

        unset($data['detail']['id'], $data['detail']['articleID']);

        return $converted;
    }

    private function getProductData(array &$data, array $converted): array
    {
        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['id'],
            $data['datum'],
            $data['changetime'],
            $data['crossbundlelook'],
            $data['mode'],
            $data['main_detail_id'],
            $data['available_from'],
            $data['available_to'],
            $data['pseudosales'],

            // TODO check how to handle these
            $data['configurator_set_id'],
            $data['configuratorOptions'],
            $data['pricegroupID'],
            $data['pricegroupActive'],
            $data['filtergroupID'],
            $data['template'],
            $data['detail']['ordernumber'],
            $data['detail']['additionaltext'],
            $data['attributes']
        );

        $converted['tax'] = $this->getTax($data['tax']);
        unset($data['tax'], $data['taxID']);

        if (isset($data['unit']['id'])) {
            $converted['unit'] = $this->getUnit($data['unit'], $data['_locale']);
        }
        unset($data['unit'], $data['detail']['unitID']);

        $setInGross = isset($data['prices'][0]['customergroup']) ? (bool) $data['prices'][0]['customergroup']['taxinput'] : false;
        $converted['price'] = $this->getPrice($data['prices'][0], $converted['tax']['taxRate'], $setInGross);
        $converted['priceRules'] = $this->getPriceRules($data['prices'], $converted);
        unset($data['prices']);

        if (isset($data['assets'])) {
            $convertedMedia = $this->getMedia($data['assets'], $converted, $data['_locale']);

            if (!empty($convertedMedia['media'])) {
                $converted['media'] = $convertedMedia['media'];
            }

            if (isset($convertedMedia['cover'])) {
                $converted['cover'] = $convertedMedia['cover'];
            }

            unset($data['assets'], $convertedMedia);
        }

        $converted['translations'] = [];
        $this->setGivenProductTranslation($data, $converted);
        unset($data['_locale']);

        if (isset($data['categories'])) {
            $converted['categories'] = $this->getCategoryMapping($data['categories']);
        }
        unset($data['categories']);

        $this->helper->convertValue($converted, 'active', $data, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'minDeliveryTime', $data, 'shippingtime', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'isCloseout', $data, 'laststock', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'markAsTopseller', $data, 'topseller', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'allowNotification', $data, 'notification', $this->helper::TYPE_BOOLEAN);

        $this->helper->convertValue($converted, 'manufacturerNumber', $data['detail'], 'suppliernumber');
        $this->helper->convertValue($converted, 'active', $data['detail'], 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'sales', $data['detail'], 'sales', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'stock', $data['detail'], 'instock', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'minStock', $data['detail'], 'stockmin', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'isCloseout', $data['detail'], 'laststock', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'position', $data['detail'], 'position', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'weight', $data['detail'], 'weight', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'width', $data['detail'], 'width', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'height', $data['detail'], 'height', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'length', $data['detail'], 'length', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'ean', $data['detail'], 'ean');
        $this->helper->convertValue($converted, 'purchaseSteps', $data['detail'], 'purchasesteps', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'maxPurchase', $data['detail'], 'maxpurchase', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'minPurchase', $data['detail'], 'minpurchase', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'purchaseUnit', $data['detail'], 'purchaseunit', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'referenceUnit', $data['detail'], 'referenceunit', $this->helper::TYPE_FLOAT);
        $this->helper->convertValue($converted, 'releaseDate', $data['detail'], 'releasedate', $this->helper::TYPE_DATETIME);
        $this->helper->convertValue($converted, 'shippingFree', $data['detail'], 'shippingfree', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'minDeliveryTime', $data['detail'], 'shippingtime', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'purchasePrice', $data['detail'], 'purchaseprice', $this->helper::TYPE_FLOAT);

        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        return $converted;
    }

    private function getManufacturer(array $manufacturerData, string $locale): array
    {
        $manufacturerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            ProductManufacturerDefinition::getEntityName(),
            $manufacturerData['id'],
            $this->context
        );

        $newData['id'] = $manufacturerUuid;
        $this->helper->convertValue($newData, 'link', $manufacturerData, 'link');

        $translations = [];
        $translations['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            ProductManufacturerTranslationDefinition::getEntityName(),
            $manufacturerData['id'] . ':' . $locale,
            $this->context
        );
        $translations['productManufacturerId'] = $manufacturerUuid;
        $this->helper->convertValue($translations, 'name', $manufacturerData, 'name');
        $this->helper->convertValue($translations, 'description', $manufacturerData, 'description');
        $this->helper->convertValue($translations, 'metaTitle', $manufacturerData, 'meta_title');
        $this->helper->convertValue($translations, 'metaDescription', $manufacturerData, 'meta_description');
        $this->helper->convertValue($translations, 'metaKeywords', $manufacturerData, 'meta_keywords');

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $locale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translations['language']['id'] = $languageData['uuid'];
            $translations['language']['localeId'] = $languageData['createData']['localeId'];
            $translations['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translations['languageId'] = $languageData['uuid'];
        }

        $newData['translations'][$languageData['uuid']] = $translations;

        return $newData;
    }

    private function getTax(array $taxData): array
    {
        return [
            'id' => $this->mappingService->createNewUuid(
                $this->connectionId,
                TaxDefinition::getEntityName(),
                $taxData['id'],
                $this->context
            ),
            'taxRate' => (float) $taxData['tax'],
            'name' => $taxData['description'],
        ];
    }

    private function getUnit(array $unitData, string $locale): array
    {
        $translation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            UnitTranslationDefinition::getEntityName(),
            $unitData['id'] . ':' . $locale,
            $this->context
        );

        $this->helper->convertValue($translation, 'shortCode', $unitData, 'unit');
        $this->helper->convertValue($translation, 'name', $unitData, 'description');

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $locale, $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $translation['language']['id'] = $languageData['uuid'];
            $translation['language']['localeId'] = $languageData['createData']['localeId'];
            $translation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $translation['languageId'] = $languageData['uuid'];
        }

        return [
            'id' => $this->mappingService->createNewUuid(
                $this->connectionId,
                UnitDefinition::getEntityName(),
                $unitData['id'],
                $this->context
            ),
            'translations' => [$translation],
        ];
    }

    private function getMedia(array $media, array $converted, $locale): array
    {
        $mediaObjects = [];
        $cover = null;
        foreach ($media as $mediaData) {
            if (!isset($mediaData['media']['id'])) {
                $this->loggingService->addInfo(
                    $this->runId,
                    Shopware55LogTypes::PRODUCT_MEDIA_NOT_CONVERTED,
                    'Product-Media could not be converted',
                    'Product-Media could not be converted.',
                    [
                        'uuid' => $converted['id'],
                        'id' => $this->oldProductId,
                    ]
                );

                continue;
            }

            $newProductMedia = [];
            $newProductMedia['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                ProductMediaDefinition::getEntityName(),
                $mediaData['id'],
                $this->context
            );
            $newProductMedia['productId'] = $converted['id'];
            $this->helper->convertValue($newProductMedia, 'position', $mediaData, 'position', $this->helper::TYPE_INTEGER);

            $newMedia = [];
            $newMedia['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                MediaDefinition::getEntityName(),
                $mediaData['media']['id'],
                $this->context
            );

            if (!isset($mediaData['media']['name'])) {
                $mediaData['media']['name'] = $newMedia['id'];
            }

            $this->mediaFileService->saveMediaFile(
                [
                    'runId' => $this->runId,
                    'uri' => $mediaData['media']['uri'] ?? $mediaData['media']['path'],
                    'fileName' => $mediaData['media']['name'],
                    'fileSize' => (int) $mediaData['media']['file_size'],
                    'mediaId' => $newMedia['id'],
                ]
            );

            $translation['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                MediaTranslationDefinition::getEntityName(),
                $mediaData['media']['id'] . ':' . $locale,
                $this->context
            );
            $this->helper->convertValue($translation, 'name', $mediaData['media'], 'name');
            $this->helper->convertValue($translation, 'description', $mediaData['media'], 'description');

            $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $locale, $this->context);

            if (isset($languageData['createData']) && !empty($languageData['createData'])) {
                $translation['language']['id'] = $languageData['uuid'];
                $translation['language']['localeId'] = $languageData['createData']['localeId'];
                $translation['language']['name'] = $languageData['createData']['localeCode'];
            } else {
                $translation['languageId'] = $languageData['uuid'];
            }

            $newMedia['translations'][$languageData['uuid']] = $translation;

            $newProductMedia['media'] = $newMedia;
            $mediaObjects[] = $newProductMedia;

            if ($cover === null && (int) $mediaData['main'] === 1) {
                $cover = $newProductMedia;
            }
        }

        return ['media' => $mediaObjects, 'cover' => $cover];
    }

    private function getPrice(array $priceData, float $taxRate, bool $setInGross): array
    {
        $gross = (float) $priceData['price'] * (1 + $taxRate / 100);
        $gross = $setInGross ? round($gross, 4) : $gross;

        return [
            'gross' => $gross,
            'net' => (float) $priceData['price'],
        ];
    }

    private function getPriceRules(array $priceData, array $converted): array
    {
        $newData = [];
        foreach ($priceData as $price) {
            $setInGross = isset($price['customergroup']) ? (bool) $price['customergroup']['taxinput'] : false;
            $newData[] = [
                'productId' => $converted['id'],
                'currencyId' => Defaults::CURRENCY,
                'rule' => [
                    'id' => $this->mappingService->createNewUuid(
                        $this->connectionId,
                        RuleDefinition::getEntityName(),
                        $price['pricegroup'],
                        $this->context
                    ),
                    'name' => $price['pricegroup'],
                    'priority' => 0,
                    'payload' => new AndRule(),
                ],
                'price' => $this->getPrice($price, $converted['tax']['taxRate'], $setInGross),
                'quantityStart' => (int) $price['from'],
                'quantityEnd' => $price['to'] !== 'beliebig' ? (int) $price['to'] : null,
            ];
        }

        return $newData;
    }

    private function setGivenProductTranslation(array &$data, array &$converted): void
    {
        $defaultTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            ProductTranslationDefinition::getEntityName(),
            $this->oldProductId . ':' . $data['_locale'],
            $this->context
        );
        $defaultTranslation['productId'] = $converted['id'];

        $this->helper->convertValue($defaultTranslation, 'name', $data, 'name');
        $this->helper->convertValue($defaultTranslation, 'description', $data, 'description');
        $this->helper->convertValue($defaultTranslation, 'descriptionLong', $data, 'description_long');
        $this->helper->convertValue($defaultTranslation, 'metaTitle', $data, 'metaTitle');
        $this->helper->convertValue($defaultTranslation, 'keywords', $data, 'keywords');
        $this->helper->convertValue($defaultTranslation, 'packUnit', $data['detail'], 'packunit');

        $languageData = $this->mappingService->getLanguageUuid($this->connectionId, $data['_locale'], $this->context);

        if (isset($languageData['createData']) && !empty($languageData['createData'])) {
            $defaultTranslation['language']['id'] = $languageData['uuid'];
            $defaultTranslation['language']['localeId'] = $languageData['createData']['localeId'];
            $defaultTranslation['language']['name'] = $languageData['createData']['localeCode'];
        } else {
            $defaultTranslation['languageId'] = $languageData['uuid'];
        }

        $converted['translations'][$languageData['uuid']] = $defaultTranslation;
    }

    private function getCategoryMapping(array $categories): array
    {
        $categoryMapping = [];

        foreach ($categories as $key => $category) {
            $categoryUuid = $this->mappingService->getUuid(
                $this->connectionId,
                CategoryDefinition::getEntityName(),
                $category['id'],
                $this->context
            );

            if ($categoryUuid === null) {
                continue;
            }

            $categoryMapping[] = ['id' => $categoryUuid];
        }

        return $categoryMapping;
    }
}
