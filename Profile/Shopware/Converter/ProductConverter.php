<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Converter;

use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Rule\Container\OrRule;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\CannotConvertChildEntity;
use SwagMigrationAssistant\Migration\Logging\Log\EmptyNecessaryFieldRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Exception\ParentEntityForChildNotFoundException;

abstract class ProductConverter extends ShopwareConverter
{
    public const MAIN_PRODUCT_TYPE = 1;
    public const VARIANT_PRODUCT_TYPE = 2;

    /**
     * @var MappingServiceInterface
     */
    protected $mappingService;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $oldProductId;

    /**
     * @var string
     */
    protected $connectionId;

    /**
     * @var string
     */
    protected $runId;

    /**
     * @var MediaFileServiceInterface
     */
    protected $mediaFileService;

    /**
     * @var LoggingServiceInterface
     */
    protected $loggingService;

    /**
     * @var string[]
     */
    protected $requiredDataFieldKeys = [
        'tax',
        'prices',
    ];

    protected $defaultValues = [
        'minPurchase' => 1,
        'purchaseSteps' => 1,
        'shippingFree' => false,
        'restockTime' => 1,
    ];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $productType;

    /**
     * @var string
     */
    protected $mainProductId;

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
        $this->context = $context;
        $this->migrationContext = $migrationContext;
        $this->runId = $migrationContext->getRunUuid();
        $this->connectionId = $migrationContext->getConnection()->getId();
        $this->oldProductId = $data['detail']['ordernumber'];
        $this->mainProductId = $data['detail']['articleID'];
        $this->locale = $data['_locale'];

        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                    $this->runId,
                    DefaultEntities::PRODUCT,
                    $this->oldProductId,
                    $field
                ));
            }

            return new ConvertStruct(null, $data);
        }

        $this->productType = (int) $data['detail']['kind'];
        unset($data['detail']['kind']);
        $isProductWithVariant = $data['configurator_set_id'] !== null;

        if ($this->productType === self::MAIN_PRODUCT_TYPE && $isProductWithVariant) {
            return $this->convertMainProduct($data);
        }

        if ($this->productType === self::VARIANT_PRODUCT_TYPE && $isProductWithVariant) {
            return $this->convertVariantProduct($data);
        }

        $converted = $this->getUuidForProduct($data);
        $converted = $this->getProductData($data, $converted);

        if (isset($data['categories'])) {
            $converted['categories'] = $this->getCategoryMapping($data['categories']);
        }
        unset($data['categories']);

        if (isset($data['shops'])) {
            $converted['visibilities'] = $this->getVisibilities($converted, $data['shops']);
        }
        unset($data['shops']);

        unset($data['detail']['id'], $data['detail']['articleID']);

        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    protected function convertMainProduct(array $data): ConvertStruct
    {
        $containerUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT . '_container',
            $data['id'],
            $this->context
        );
        $converted['id'] = $containerUuid;
        unset($data['detail']['articleID']);

        $converted = $this->getProductData($data, $converted);

        $converted['children'][] = $converted;
        $converted['productNumber'] .= 'M';
        $mainProductUuid = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $this->oldProductId,
            $this->context
        );
        $converted['children'][0]['id'] = $mainProductUuid;

        if (isset($converted['children'][0]['media'])) {
            if (isset($converted['children'][0]['cover'])) {
                $coverMediaUuid = $converted['children'][0]['cover']['media']['id'];
            }
            foreach ($converted['children'][0]['media'] as &$media) {
                $productMediaRelationUuid = $this->mappingService->createNewUuid(
                    $this->connectionId,
                    DefaultEntities::PRODUCT_MEDIA,
                    $media['id'],
                    $this->context
                );
                $media['productId'] = $mainProductUuid;
                $media['id'] = $productMediaRelationUuid;

                if (isset($coverMediaUuid) && $media['media']['id'] === $coverMediaUuid) {
                    $converted['children'][0]['cover'] = $media;
                }
            }
        }
        $converted['children'][0]['parentId'] = $containerUuid;
        unset($data['detail']['id']);

        if (isset($data['categories'])) {
            $converted['categories'] = $this->getCategoryMapping($data['categories']);
        }
        unset($data['categories']);

        if (isset($data['shops'])) {
            $converted['visibilities'] = $this->getVisibilities($converted, $data['shops']);
        }
        unset($data['shops']);

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
    protected function convertVariantProduct(array $data): ConvertStruct
    {
        $parentUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT . '_container',
            $data['detail']['articleID'],
            $this->context
        );

        if ($parentUuid === null) {
            throw new ParentEntityForChildNotFoundException(DefaultEntities::PRODUCT, $this->oldProductId);
        }

        $converted = $this->getUuidForProduct($data);
        $converted['parentId'] = $parentUuid;
        $converted = $this->getProductData($data, $converted);
        unset($data['detail']['id'], $data['detail']['articleID'], $data['categories']);

        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    private function getUuidForProduct(array &$data): array
    {
        $converted['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $this->oldProductId,
            $this->context
        );

        $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT . '_mainProduct',
            $data['detail']['articleID'],
            $this->context,
            null,
            $converted['id']
        );

        return $converted;
    }

    private function getProductData(array &$data, array $converted): array
    {
        if (isset($data['manufacturer'])) {
            $converted['manufacturer'] = $this->getManufacturer($data['manufacturer']);
        }
        unset($data['manufacturer'], $data['supplierID']);

        $converted['tax'] = $this->getTax($data['tax']);
        unset($data['tax'], $data['taxID']);

        if (isset($data['unit']['id'])) {
            $converted['unit'] = $this->getUnit($data['unit']);
        }
        unset($data['unit'], $data['detail']['unitID']);

        $setInGross = isset($data['prices'][0]['customergroup']) ? (bool) $data['prices'][0]['customergroup']['taxinput'] : false;
        $converted['price'] = $this->getPrice($data['prices'][0], $converted['tax']['taxRate'], $setInGross);

        if (empty($converted['price'])) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::PRODUCT,
                $this->oldProductId,
                'currency'
            ));
        }

        $converted['prices'] = $this->getPrices($data['prices'], $converted);
        unset($data['prices']);

        if (isset($data['assets'])) {
            $convertedMedia = $this->getMedia($data['assets'], $data['detail']['id'], $converted);

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

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::PRODUCT, $this->migrationContext->getConnection()->getName(), ['id', 'articleID', 'articledetailsID']);
        }
        unset($data['attributes']);

        $this->convertValue($converted, 'productNumber', $data['detail'], 'ordernumber', self::TYPE_STRING);

        if ($this->productType === self::MAIN_PRODUCT_TYPE) {
            $this->convertValue($converted, 'active', $data, 'active', self::TYPE_BOOLEAN);
            unset($data['detail']['active']);
        } else {
            $this->convertValue($converted, 'active', $data['detail'], 'active', self::TYPE_BOOLEAN);
            unset($data['active']);
        }

        $this->convertValue($converted, 'isCloseout', $data, 'laststock', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'markAsTopseller', $data, 'topseller', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'allowNotification', $data, 'notification', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'manufacturerNumber', $data['detail'], 'suppliernumber');
        $this->convertValue($converted, 'sales', $data['detail'], 'sales', self::TYPE_INTEGER);
        $this->convertValue($converted, 'stock', $data['detail'], 'instock', self::TYPE_INTEGER);
        $this->convertValue($converted, 'minStock', $data['detail'], 'stockmin', self::TYPE_INTEGER);
        $this->convertValue($converted, 'isCloseout', $data['detail'], 'laststock', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'position', $data['detail'], 'position', self::TYPE_INTEGER);
        $this->convertValue($converted, 'weight', $data['detail'], 'weight', self::TYPE_FLOAT);
        $this->convertValue($converted, 'width', $data['detail'], 'width', self::TYPE_FLOAT);
        $this->convertValue($converted, 'height', $data['detail'], 'height', self::TYPE_FLOAT);
        $this->convertValue($converted, 'length', $data['detail'], 'length', self::TYPE_FLOAT);
        $this->convertValue($converted, 'ean', $data['detail'], 'ean');
        $this->convertValue($converted, 'purchaseSteps', $data['detail'], 'purchasesteps', self::TYPE_INTEGER);
        $this->convertValue($converted, 'maxPurchase', $data['detail'], 'maxpurchase', self::TYPE_INTEGER);
        $this->convertValue($converted, 'minPurchase', $data['detail'], 'minpurchase', self::TYPE_INTEGER);
        $this->convertValue($converted, 'purchaseUnit', $data['detail'], 'purchaseunit', self::TYPE_FLOAT);
        $this->convertValue($converted, 'referenceUnit', $data['detail'], 'referenceunit', self::TYPE_FLOAT);
        $this->convertValue($converted, 'releaseDate', $data['detail'], 'releasedate', self::TYPE_DATETIME);
        $this->convertValue($converted, 'shippingFree', $data['detail'], 'shippingfree', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'purchasePrice', $data['detail'], 'purchaseprice', self::TYPE_FLOAT);

        if (isset($data['detail']['shippingtime'])) {
            $deliveryTime = $this->getDeliveryTime($data['detail']['shippingtime']);

            if ($deliveryTime !== null) {
                $converted['deliveryTime'] = $deliveryTime;
            }

            unset($data['detail']['shippingtime']);
        }

        $this->getOptions($converted, $data);
        $this->getFilters($data);

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
            $data['configurator_set_id'],

            // TODO check how to handle these
            $data['pricegroupID'],
            $data['pricegroupActive'],
            $data['filtergroupID'],
            $data['template'],
            $data['detail']['additionaltext'],
            $data['shippingtime']
        );

        foreach ($this->defaultValues as $key => $value) {
            if (!isset($converted[$key])) {
                $converted[$key] = $value;
                continue;
            }

            if (is_numeric($value) && $value > $converted[$key]) {
                $converted[$key] = $value;
                continue;
            }
        }

        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        return $converted;
    }

    private function getDeliveryTime($shippingTime): ?array
    {
        $convertedDeliveryTime = [
            'min' => 0,
            'max' => 0,
            'unit' => 'day',
        ];

        $deliveryTime = [];
        preg_match('/([0-9]*)\s*-\s*([0-9]*)/', $shippingTime, $deliveryTime);

        if (empty($deliveryTime)) {
            preg_match('/([0-9]*)\s*/', $shippingTime, $deliveryTime);
        }

        if (empty($deliveryTime)) {
            $deliveryTime['min'] = (int) $shippingTime;
        }

        if (isset($deliveryTime[1])) {
            $convertedDeliveryTime['min'] = (int) $deliveryTime[1];
        }

        if (isset($deliveryTime[2])) {
            $convertedDeliveryTime['max'] = (int) $deliveryTime[2];
        }

        if ($convertedDeliveryTime['min'] !== 0 || $convertedDeliveryTime['max'] !== 0) {
            $convertedDeliveryTime['name'] = $convertedDeliveryTime['min'] . '-' . $convertedDeliveryTime['max'] . ' ' . $convertedDeliveryTime['unit'] . 's';

            if ($convertedDeliveryTime['max'] === 0) {
                $convertedDeliveryTime['name'] = $convertedDeliveryTime['min'] . ' ' . $convertedDeliveryTime['unit'];
                if ($convertedDeliveryTime['min'] > 1) {
                    $convertedDeliveryTime['name'] = $convertedDeliveryTime['min'] . ' ' . $convertedDeliveryTime['unit'] . 's';
                }
            }

            $convertedDeliveryTime['id'] = $this->mappingService->getDeliveryTime(
                $this->connectionId,
                $this->context,
                $convertedDeliveryTime['min'],
                $convertedDeliveryTime['max'],
                $convertedDeliveryTime['unit'],
                $convertedDeliveryTime['name']
            );

            return $convertedDeliveryTime;
        }

        return null;
    }

    private function getOptions(&$converted, &$data): void
    {
        if (
            !isset($data['configuratorOptions'])
            || !is_array($data['configuratorOptions'])
        ) {
            return;
        }

        $options = [];
        $productContainerUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT . '_container',
            $this->mainProductId,
            $this->context
        );

        $language = $this->mappingService->getDefaultLanguage($this->context);
        $shouldBeTranslated = true;
        if ($language->getLocale()->getCode() === $this->locale) {
            $shouldBeTranslated = false;
        }

        foreach ($data['configuratorOptions'] as $option) {
            if ($productContainerUuid !== null) {
                $this->mappingService->createNewUuidListItem(
                    $this->connectionId,
                    'main_product_options',
                    hash('md5', strtolower($option['name'] . '_' . $option['group']['name'])),
                    $this->context,
                    null,
                    $productContainerUuid
                );
            }

            $optionElement = [
                'id' => $this->mappingService->createNewUuid(
                    $this->connectionId,
                    DefaultEntities::PROPERTY_GROUP_OPTION,
                    hash('md5', strtolower($option['name'] . '_' . $option['group']['name'])),
                    $this->context
                ),

                'group' => [
                    'id' => $this->mappingService->createNewUuid(
                        $this->connectionId,
                        DefaultEntities::PROPERTY_GROUP,
                        hash('md5', strtolower($option['group']['name'])),
                        $this->context
                    ),
                ],
            ];

            if ($shouldBeTranslated) {
                $this->getOptionTranslation($optionElement, $option);
            }

            $this->convertValue($optionElement, 'name', $option, 'name');
            $this->convertValue($optionElement, 'position', $option, 'position', self::TYPE_INTEGER);

            $this->convertValue($optionElement['group'], 'name', $option['group'], 'name');
            $this->convertValue($optionElement['group'], 'description', $option['group'], 'description');

            $options[] = $optionElement;
        }
        unset($data['configuratorOptions']);

        $converted['options'] = $options;
    }

    private function getFilters(&$data): void
    {
        if (
            !isset($data['filters'])
            || !is_array($data['filters'])
        ) {
            return;
        }

        $productContainerUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT . '_container',
            $this->mainProductId,
            $this->context
        );

        if ($productContainerUuid === null) {
            $productContainerUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::PRODUCT . '_mainProduct',
                $this->mainProductId,
                $this->context
            );
        }

        if ($productContainerUuid === null) {
            return;
        }

        foreach ($data['filters'] as $option) {
            if (!isset($option['value'], $option['option']['name'])) {
                continue;
            }

            $this->mappingService->createNewUuidListItem(
                $this->connectionId,
                'main_product_filter',
                hash('md5', strtolower($option['value'] . '_' . $option['option']['name'])),
                $this->context,
                null,
                $productContainerUuid
            );
        }
        unset($data['filters']);
    }

    private function getOptionTranslation(array &$option, array $data): void
    {
        $localeOptionTranslation = [];
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeOptionTranslation['languageId'] = $languageUuid;
        $localeGroupTranslation = $localeOptionTranslation;

        $localeOptionTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION,
            hash('md5', strtolower($data['name'] . '_' . $data['group']['name'])) . ':' . $this->locale,
            $this->context
        );

        $this->convertValue($localeOptionTranslation, 'name', $data, 'name');
        $this->convertValue($localeOptionTranslation, 'position', $data, 'position', self::TYPE_INTEGER);

        $localeGroupTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_TRANSLATION,
            hash('md5', strtolower($data['group']['name'])) . ':' . $this->locale,
            $this->context
        );

        $this->convertValue($localeGroupTranslation, 'name', $data['group'], 'name');
        $this->convertValue($localeGroupTranslation, 'description', $data['group'], 'description');

        $option['translations'][$languageUuid] = $localeOptionTranslation;
        $option['group']['translations'][$languageUuid] = $localeGroupTranslation;
    }

    private function getManufacturer(array $data): array
    {
        $manufacturer['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER,
            $data['id'],
            $this->context
        );

        $this->getManufacturerTranslation($manufacturer, $data);
        $this->convertValue($manufacturer, 'link', $data, 'link');
        $this->convertValue($manufacturer, 'name', $data, 'name');
        $this->convertValue($manufacturer, 'description', $data, 'description');

        if (isset($data['media'])) {
            $manufacturer['media'] = $this->getManufacturerMedia($data['media']);
        }

        if (isset($data['attributes'])) {
            $manufacturer['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::PRODUCT_MANUFACTURER, $this->migrationContext->getConnection()->getName(), ['id', 'supplierID']);
        }

        return $manufacturer;
    }

    private function getManufacturerTranslation(array &$manufacturer, array $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];
        $localeTranslation['productManufacturerId'] = $manufacturer['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'name');
        $this->convertValue($localeTranslation, 'description', $data, 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER_TRANSLATION,
            $data['id'] . ':' . $this->locale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $manufacturer['translations'][$languageUuid] = $localeTranslation;
    }

    private function getTax(array $taxData): array
    {
        $taxRate = (float) $taxData['tax'];
        $taxUuid = $this->mappingService->getTaxUuid($this->connectionId, $taxRate, $this->context);

        if (empty($taxUuid)) {
            $taxUuid = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::TAX,
                $taxData['id'],
                $this->context
            );
        }

        return [
            'id' => $taxUuid,
            'taxRate' => $taxRate,
            'name' => $taxData['description'],
        ];
    }

    private function getUnit(array $data): array
    {
        $unit = [];
        $unit['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::UNIT,
            $data['id'],
            $this->context
        );

        $this->getUnitTranslation($unit, $data);
        $this->convertValue($unit, 'shortCode', $data, 'unit');
        $this->convertValue($unit, 'name', $data, 'description');

        return $unit;
    }

    private function getUnitTranslation(array &$unit, $data): void
    {
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $this->convertValue($localeTranslation, 'shortCode', $data, 'unit');
        $this->convertValue($localeTranslation, 'name', $data, 'description');

        $localeTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::UNIT_TRANSLATION,
            $data['id'] . ':' . $this->locale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        $unit['translations'][$languageUuid] = $localeTranslation;
    }

    private function getMedia(array $media, string $oldVariantId, array $converted): array
    {
        $mediaObjects = [];
        $cover = null;
        foreach ($media as $mediaData) {
            if (!isset($mediaData['media']['id'])) {
                $this->loggingService->addLogEntry(new CannotConvertChildEntity(
                    $this->runId,
                    DefaultEntities::PRODUCT_MEDIA,
                    DefaultEntities::PRODUCT,
                    $this->oldProductId
                ));

                continue;
            }

            $newProductMedia = [];
            $newProductMedia['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::PRODUCT_MEDIA,
                $oldVariantId . $mediaData['id'],
                $this->context
            );
            $newProductMedia['productId'] = $converted['id'];
            $this->convertValue($newProductMedia, 'position', $mediaData, 'position', self::TYPE_INTEGER);

            $newMedia = [];
            $newMedia['id'] = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::MEDIA,
                $mediaData['media']['id'],
                $this->context
            );

            if (!isset($mediaData['media']['name'])) {
                $mediaData['media']['name'] = $newMedia['id'];
            }

            $this->mediaFileService->saveMediaFile(
                [
                    'runId' => $this->runId,
                    'entity' => MediaDataSet::getEntity(),
                    'uri' => $mediaData['media']['uri'] ?? $mediaData['media']['path'],
                    'fileName' => $mediaData['media']['name'],
                    'fileSize' => (int) $mediaData['media']['file_size'],
                    'mediaId' => $newMedia['id'],
                ]
            );

            $this->getMediaTranslation($newMedia, $mediaData);
            $this->convertValue($newMedia, 'name', $mediaData['media'], 'name');
            $this->convertValue($newMedia, 'description', $mediaData['media'], 'description');

            $albumUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::MEDIA_FOLDER,
                $mediaData['media']['albumID'],
                $this->context
            );

            if ($albumUuid !== null) {
                $newMedia['mediaFolderId'] = $albumUuid;
            }

            $newProductMedia['media'] = $newMedia;
            $mediaObjects[] = $newProductMedia;

            if ($cover === null && (int) $mediaData['main'] === 1) {
                $cover = $newProductMedia;
            }
        }

        return ['media' => $mediaObjects, 'cover' => $cover];
    }

    // Todo: Check if this is necessary, because name and description is currently not translatable
    private function getMediaTranslation(array &$media, array $data): void
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

    private function getManufacturerMedia(array $media): array
    {
        $manufacturerMedia['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $media['id'],
            $this->context
        );

        if (empty($media['name'])) {
            $media['name'] = $manufacturerMedia['id'];
        }

        $this->getMediaTranslation($manufacturerMedia, ['media' => $media]);

        $albumUuid = $this->mappingService->getUuid(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $media['albumID'],
            $this->context
        );

        if ($albumUuid !== null) {
            $manufacturerMedia['mediaFolderId'] = $albumUuid;
        }

        $this->mediaFileService->saveMediaFile(
            [
                'runId' => $this->runId,
                'entity' => MediaDataSet::getEntity(),
                'uri' => $media['uri'] ?? $media['path'],
                'fileName' => $media['name'],
                'fileSize' => (int) $media['file_size'],
                'mediaId' => $manufacturerMedia['id'],
            ]
        );

        return $manufacturerMedia;
    }

    private function getPrice(array $priceData, float $taxRate, bool $setInGross): array
    {
        $gross = (float) $priceData['price'] * (1 + $taxRate / 100);
        $gross = $setInGross ? round($gross, 4) : $gross;

        $currencyUuid = null;
        if (isset($priceData['currencyShortName'])) {
            $currencyUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::CURRENCY,
                $priceData['currencyShortName'],
                $this->context
            );
        }
        if ($currencyUuid === null) {
            return [];
        }

        $price = [];
        if ($currencyUuid !== Defaults::CURRENCY) {
            $price[] = [
                'currencyId' => Defaults::CURRENCY,
                'gross' => $gross,
                'net' => (float) $priceData['price'],
                'linked' => true,
            ];
        }

        $price[] = [
            'currencyId' => $currencyUuid,
            'gross' => $gross,
            'net' => (float) $priceData['price'],
            'linked' => true,
        ];

        return $price;
    }

    private function getPrices(array $priceData, array $converted): array
    {
        $newData = [];
        foreach ($priceData as $price) {
            if (!isset($price['customergroup']['id'])) {
                continue;
            }

            $customerGroupUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::CUSTOMER_GROUP,
                $price['customergroup']['id'],
                $this->context
            );

            if (!isset($customerGroupUuid)) {
                continue;
            }

            $productPriceRuleUuid = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_productPriceRule_' . $price['id'] . '_' . $price['customergroup']['id'],
                $this->context
            );

            $priceRuleUuid = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_' . $price['customergroup']['id'],
                $this->context
            );

            $orContainerUuid = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_orContainer_' . $price['customergroup']['id'],
                $this->context
            );

            $andContainerUuid = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_andContainer_' . $price['customergroup']['id'],
                $this->context
            );

            $conditionUuid = $this->mappingService->createNewUuid(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_condition_' . $price['customergroup']['id'],
                $this->context
            );

            $setInGross = (bool) $price['customergroup']['taxinput'];

            $priceArray = $this->getPrice($price, $converted['tax']['taxRate'], $setInGross);

            if (empty($priceArray)) {
                $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                    $this->runId,
                    DefaultEntities::PRODUCT_PRICE,
                    $this->oldProductId,
                    'currencyId'
                ));

                continue;
            }

            $data = [
                'id' => $productPriceRuleUuid,
                'productId' => $converted['id'],
                'rule' => [
                    'id' => $priceRuleUuid,
                    'name' => $price['customergroup']['description'],
                    'priority' => 0,
                    'moduleTypes' => [
                        'types' => [
                            'price',
                        ],
                    ],
                    'conditions' => [
                        [
                            'id' => $orContainerUuid,
                            'type' => (new OrRule())->getName(),
                            'value' => [],
                        ],

                        [
                            'id' => $andContainerUuid,
                            'type' => (new AndRule())->getName(),
                            'parentId' => $orContainerUuid,
                            'value' => [],
                        ],

                        [
                            'id' => $conditionUuid,
                            'type' => 'customerCustomerGroup',
                            'parentId' => $andContainerUuid,
                            'position' => 1,
                            'value' => [
                                'customerGroupIds' => [
                                    $customerGroupUuid,
                                ],
                                'operator' => '=',
                            ],
                        ],
                    ],
                ],
                'price' => $priceArray,
                'quantityStart' => (int) $price['from'],
                'quantityEnd' => $price['to'] !== 'beliebig' ? (int) $price['to'] : null,
            ];

            if (isset($price['attributes'])) {
                $data['customFields'] = $this->getAttributes($price, DefaultEntities::PRODUCT_PRICE, $this->migrationContext->getConnection()->getName(), ['id', 'priceID']);
            }

            $newData[] = $data;
        }

        return $newData;
    }

    private function setGivenProductTranslation(array &$data, array &$converted): void
    {
        $originalData = $data;
        $this->convertValue($converted, 'name', $data, 'name');
        $this->convertValue($converted, 'keywords', $data, 'keywords');
        $this->convertValue($converted, 'description', $data, 'description_long');
        $this->convertValue($converted, 'metaTitle', $data, 'metaTitle');
        $this->convertValue($converted, 'packUnit', $data['detail'], 'packunit');
        unset($data['description']); // Todo: Use this for meta_description

        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language->getLocale()->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $localeTranslation['productId'] = $converted['id'];
        $this->convertValue($localeTranslation, 'name', $originalData, 'name');
        $this->convertValue($localeTranslation, 'keywords', $originalData, 'keywords');
        $this->convertValue($localeTranslation, 'description', $originalData, 'description_long');
        $this->convertValue($localeTranslation, 'metaTitle', $originalData, 'metaTitle');
        $this->convertValue($localeTranslation, 'packUnit', $originalData['detail'], 'packunit');

        $defaultTranslation['id'] = $this->mappingService->createNewUuid(
            $this->connectionId,
            DefaultEntities::PRODUCT_TRANSLATION,
            $this->oldProductId . ':' . $this->locale,
            $this->context
        );

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        if (isset($data['attributes'])) {
            $localeTranslation['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::PRODUCT, $this->migrationContext->getConnection()->getName(), ['id', 'articleID', 'articledetailsID']);
        }

        $converted['translations'][$languageUuid] = $localeTranslation;
    }

    private function getCategoryMapping(array $categories): array
    {
        $categoryMapping = [];

        foreach ($categories as $key => $category) {
            $categoryUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::CATEGORY,
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

    private function getVisibilities(array $converted, array $shops): array
    {
        $visibilities = [];

        foreach ($shops as $shop) {
            $salesChannelUuid = $this->mappingService->getUuid(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $shop,
                $this->context
            );

            if ($salesChannelUuid !== null) {
                $visibilities[] = [
                    'id' => $this->mappingService->createNewUuid(
                        $this->connectionId,
                        DefaultEntities::PRODUCT_VISIBILITY,
                        $this->oldProductId . '_' . $shop,
                        $this->context
                    ),
                    'productId' => $converted['id'],
                    'salesChannelId' => $salesChannelUuid,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ];
            }
        }

        return $visibilities;
    }
}
