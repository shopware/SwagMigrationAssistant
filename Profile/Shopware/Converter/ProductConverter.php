<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @var string[]
     */
    protected $requiredDataFieldKeys = [
        'tax',
        'prices',
    ];

    /**
     * @var array
     */
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

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var string|null
     */
    protected $currencyUuid;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        MediaFileServiceInterface $mediaFileService
    ) {
        parent::__construct($mappingService, $loggingService);

        $this->mediaFileService = $mediaFileService;
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['detail']['ordernumber'];
    }

    public function getMediaUuids(array $converted): ?array
    {
        $mediaUuids = [];
        foreach ($converted as $data) {
            if (isset($data['media'])) {
                foreach ($data['media'] as $media) {
                    if (!isset($media['media'])) {
                        continue;
                    }

                    $mediaUuids[] = $media['media']['id'];
                }
            }

            if (isset($data['manufacturer']['media']['id'])) {
                $mediaUuids[] = $data['manufacturer']['media']['id'];
            }
        }

        return $mediaUuids;
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->migrationContext = $migrationContext;
        $this->runId = $migrationContext->getRunUuid();
        $this->oldProductId = $data['detail']['ordernumber'];
        $this->mainProductId = $data['detail']['articleID'];
        $this->locale = $data['_locale'];

        $connection = $migrationContext->getConnection();
        $this->connectionName = '';
        $this->connectionId = '';
        if ($connection !== null) {
            $this->connectionId = $connection->getId();
            $this->connectionName = $connection->getName();
        }

        $fields = $this->checkForEmptyRequiredDataFields($data, $this->requiredDataFieldKeys);
        if (!empty($fields)) {
            $this->loggingService->addLogEntry(new EmptyNecessaryFieldRunLog(
                $this->runId,
                DefaultEntities::PRODUCT,
                $this->oldProductId,
                \implode(',', $fields)
            ));

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

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($migrationContext, $context);

        $mainMapping = $this->mainMapping['id'] ?? null;

        return new ConvertStruct($converted, $returnData, $mainMapping);
    }

    protected function convertMainProduct(array $data): ConvertStruct
    {
        $containerMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            $data['id'],
            $this->context
        );
        $containerUuid = $containerMapping['entityUuid'];

        $converted = [];
        $converted['id'] = $containerUuid;
        $this->mappingIds[] = $containerMapping['id'];
        unset($data['detail']['articleID']);

        $converted = $this->getProductData($data, $converted);

        $converted['children'][] = $converted;
        $converted['productNumber'] .= 'M';
        // Remove options from product container as in core
        unset($converted['options']);
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $this->oldProductId,
            $this->context,
            $this->checksum
        );
        $converted['children'][0]['id'] = $this->mainMapping['entityUuid'];

        if (isset($converted['children'][0]['media'])) {
            if (isset($converted['children'][0]['cover'])) {
                $coverMediaUuid = $converted['children'][0]['cover']['media']['id'];
            }
            foreach ($converted['children'][0]['media'] as &$media) {
                $productMediaRelationMapping = $this->mappingService->getOrCreateMapping(
                    $this->connectionId,
                    DefaultEntities::PRODUCT_MEDIA,
                    $media['id'],
                    $this->context
                );
                $productMediaRelationUuid = $productMediaRelationMapping['entityUuid'];
                $this->mappingIds[] = $productMediaRelationMapping['id'];
                $media['productId'] = $this->mainMapping['entityUuid'];
                $media['id'] = $productMediaRelationUuid;

                if (isset($coverMediaUuid) && $media['media']['id'] === $coverMediaUuid) {
                    $converted['children'][0]['cover'] = $media;
                }
            }
        }
        $converted['children'][0]['parentId'] = $containerUuid;
        unset($data['detail']['id'], $converted['children'][0]['translations'], $converted['children'][0]['customFields']);

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

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        return new ConvertStruct($converted, $returnData, $this->mainMapping['id']);
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    protected function convertVariantProduct(array $data): ConvertStruct
    {
        $parentMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_CONTAINER,
            $data['detail']['articleID'],
            $this->context
        );

        if ($parentMapping === null) {
            throw new ParentEntityForChildNotFoundException(DefaultEntities::PRODUCT, $this->oldProductId);
        }

        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $this->oldProductId,
            $this->context,
            $this->checksum
        );

        $converted = [];
        $converted['id'] = $this->mainMapping['entityUuid'];
        $converted['parentId'] = $parentMapping['entityUuid'];
        $this->mappingIds[] = $parentMapping['id'];
        $converted = $this->getProductData($data, $converted);
        unset($data['detail']['id'], $data['detail']['articleID'], $data['categories']);

        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        $returnData = $data;
        if (empty($returnData)) {
            $returnData = null;
        }
        $this->updateMainMapping($this->migrationContext, $this->context);

        $mainMapping = $this->mainMapping['id'] ?? null;

        return new ConvertStruct($converted, $returnData, $mainMapping);
    }

    private function getUuidForProduct(array &$data): array
    {
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT,
            $this->oldProductId,
            $this->context,
            $this->checksum
        );

        $converted = [];
        $converted['id'] = $this->mainMapping['entityUuid'];

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MAIN,
            $data['detail']['articleID'],
            $this->context,
            null,
            null,
            $converted['id']
        );
        $this->mappingIds[] = $mapping['id'];

        return $converted;
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
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

        $converted['price'] = $this->getPrice($data['prices'][0], $converted['tax']['taxRate']);

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

        if ($converted['translations'] === []) {
            unset($converted['translations']);
        }

        if (isset($data['attributes'])) {
            $converted['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::PRODUCT, $this->connectionName, ['id', 'articleID', 'articledetailsID'], $this->context);
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

        $this->convertValue($converted, 'createdAt', $data, 'datum', self::TYPE_DATETIME);
        $this->convertValue($converted, 'isCloseout', $data, 'laststock', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'markAsTopseller', $data, 'topseller', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'allowNotification', $data, 'notification', self::TYPE_BOOLEAN);
        $this->convertValue($converted, 'manufacturerNumber', $data['detail'], 'suppliernumber');
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

        $this->setPurchasePrices($data, $converted);

        if (isset($data['detail']['shippingtime'])) {
            $deliveryTime = $this->getDeliveryTime($data['detail']['shippingtime']);

            if ($deliveryTime !== null) {
                $converted['deliveryTime'] = $deliveryTime;
            }

            unset($data['detail']['shippingtime']);
        }

        $this->getOptions($converted, $data);

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
            $data['pricegroupID'],
            $data['pricegroupActive'],
            $data['filtergroupID'],
            $data['template'],
            $data['detail']['additionaltext'],
            $data['detail']['sales'],
            $data['shippingtime']
        );

        foreach ($this->defaultValues as $key => $value) {
            if (!isset($converted[$key])) {
                $converted[$key] = $value;

                continue;
            }

            if (\is_numeric($value) && $value > $converted[$key]) {
                $converted[$key] = $value;

                continue;
            }
        }

        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        return $converted;
    }

    private function setPurchasePrices(array &$data, array &$converted): void
    {
        if ($this->currencyUuid === null) {
            return;
        }

        $purchasePrice = 0.0;
        if (isset($data['detail']['purchaseprice'])) {
            $purchasePrice = (float) $data['detail']['purchaseprice'];
        }
        unset($data['detail']['purchaseprice']);

        $price = [];
        if ($this->currencyUuid !== Defaults::CURRENCY) {
            $price[] = [
                'currencyId' => Defaults::CURRENCY,
                'gross' => $purchasePrice,
                'net' => $purchasePrice,
                'linked' => true,
            ];
        }

        $price[] = [
            'currencyId' => $this->currencyUuid,
            'gross' => $purchasePrice,
            'net' => $purchasePrice,
            'linked' => true,
        ];

        $converted['purchasePrices'] = $price;
    }

    private function getDeliveryTime(string $shippingTime): ?array
    {
        $convertedDeliveryTime = [
            'min' => 0,
            'max' => 0,
            'unit' => 'day',
        ];

        $deliveryTime = [];
        \preg_match('/([0-9]*)\s*-\s*([0-9]*)/', $shippingTime, $deliveryTime);

        if (empty($deliveryTime)) {
            \preg_match('/([0-9]*)\s*/', $shippingTime, $deliveryTime);
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

    private function getOptions(array &$converted, array &$data): void
    {
        if (
            !isset($data['configuratorOptions'])
            || !\is_array($data['configuratorOptions'])
        ) {
            return;
        }

        $options = [];

        $shouldBeTranslated = true;
        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            $shouldBeTranslated = false;
        } else {
            $locale = $language->getLocale();
            if ($locale === null || $locale->getCode() === $this->locale) {
                $shouldBeTranslated = false;
            }
        }

        foreach ($data['configuratorOptions'] as $option) {
            $optionMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PROPERTY_GROUP_OPTION,
                \hash('md5', \mb_strtolower($option['name'] . '_' . $option['group']['name'])),
                $this->context
            );
            $this->mappingIds[] = $optionMapping['id'];
            $optionGroupMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PROPERTY_GROUP,
                \hash('md5', \mb_strtolower($option['group']['name'])),
                $this->context
            );
            $this->mappingIds[] = $optionGroupMapping['id'];
            $optionElement = [
                'id' => $optionMapping['entityUuid'],
                'group' => [
                    'id' => $optionGroupMapping['entityUuid'],
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

    private function getManufacturer(array $data): array
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER,
            $data['id'],
            $this->context
        );
        $manufacturer = [];
        $manufacturer['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $this->getManufacturerTranslation($manufacturer, $data);
        $this->convertValue($manufacturer, 'link', $data, 'link');
        $this->convertValue($manufacturer, 'name', $data, 'name');
        $this->convertValue($manufacturer, 'description', $data, 'description');

        if (isset($data['media'])) {
            $manufacturer['media'] = $this->getManufacturerMedia($data['media']);
        }

        if (isset($data['attributes'])) {
            $manufacturer['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::PRODUCT_MANUFACTURER, $this->connectionName, ['id', 'supplierID'], $this->context);
        }

        return $manufacturer;
    }

    private function getManufacturerTranslation(array &$manufacturer, array $data): void
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
        $localeTranslation['productManufacturerId'] = $manufacturer['id'];

        $this->convertValue($localeTranslation, 'name', $data, 'name');
        $this->convertValue($localeTranslation, 'description', $data, 'description');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_MANUFACTURER_TRANSLATION,
            $data['id'] . ':' . $this->locale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $manufacturer['translations'][$languageUuid] = $localeTranslation;
        }
    }

    private function getTax(array $taxData): array
    {
        $taxRate = (float) $taxData['tax'];
        $taxUuid = $this->mappingService->getTaxUuid($this->connectionId, $taxRate, $this->context);

        if (empty($taxUuid)) {
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::TAX,
                $taxData['id'],
                $this->context
            );
            $taxUuid = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];
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
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::UNIT,
            $data['id'],
            $this->context
        );
        $unit['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $this->getUnitTranslation($unit, $data);
        $this->convertValue($unit, 'shortCode', $data, 'unit');
        $this->convertValue($unit, 'name', $data, 'description');

        return $unit;
    }

    private function getUnitTranslation(array &$unit, array $data): void
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

        $this->convertValue($localeTranslation, 'shortCode', $data, 'unit');
        $this->convertValue($localeTranslation, 'name', $data, 'description');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::UNIT_TRANSLATION,
            $data['id'] . ':' . $this->locale,
            $this->context
        );
        $localeTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);

        if ($languageUuid !== null) {
            $localeTranslation['languageId'] = $languageUuid;
            $unit['translations'][$languageUuid] = $localeTranslation;
        }
    }

    private function getMedia(array $media, string $oldVariantId, array $converted): array
    {
        $mediaObjects = [];
        $cover = null;
        $lastPosition = null;
        $main = false;
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
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::PRODUCT_MEDIA,
                $oldVariantId . $mediaData['id'],
                $this->context
            );
            $newProductMedia['id'] = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];
            $newProductMedia['productId'] = $converted['id'];
            $this->convertValue($newProductMedia, 'position', $mediaData, 'position', self::TYPE_INTEGER);

            $newMedia = [];
            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::MEDIA,
                $mediaData['media']['id'],
                $this->context
            );
            $newMedia['id'] = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];

            if (empty($mediaData['media']['name'])) {
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
            $this->convertValue($newMedia, 'title', $mediaData['media'], 'name');
            $this->convertValue($newMedia, 'alt', $mediaData, 'description');

            $albumMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::MEDIA_FOLDER,
                $mediaData['media']['albumID'],
                $this->context
            );

            if ($albumMapping !== null) {
                $newMedia['mediaFolderId'] = $albumMapping['entityUuid'];
                $this->mappingIds[] = $albumMapping['id'];
            }

            $newProductMedia['media'] = $newMedia;
            $mediaObjects[] = $newProductMedia;

            if ($main === true) {
                continue;
            }

            if ($cover === null
                || (int) $mediaData['main'] === 1
                || ((int) $newProductMedia['position'] < $lastPosition && (int) $mediaData['main'] !== 1)
            ) {
                $cover = $newProductMedia;
                $lastPosition = (int) $newProductMedia['position'];
                if ((int) $mediaData['main'] === 1) {
                    $main = true;
                }
            }
        }

        return ['media' => $mediaObjects, 'cover' => $cover];
    }

    private function getMediaTranslation(array &$media, array $data): void
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
        $this->convertValue($localeTranslation, 'alt', $data, 'description');

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

    private function getManufacturerMedia(array $media): array
    {
        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::MEDIA,
            $media['id'],
            $this->context
        );
        $manufacturerMedia = [];
        $manufacturerMedia['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        if (empty($media['name'])) {
            $media['name'] = $manufacturerMedia['id'];
        }

        $this->getMediaTranslation($manufacturerMedia, ['media' => $media]);

        $albumMapping = $this->mappingService->getMapping(
            $this->connectionId,
            DefaultEntities::MEDIA_FOLDER,
            $media['albumID'],
            $this->context
        );

        if ($albumMapping !== null) {
            $manufacturerMedia['mediaFolderId'] = $albumMapping['entityUuid'];
            $this->mappingIds[] = $albumMapping['id'];
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

    private function getOptionTranslation(array &$option, array $data): void
    {
        $localeOptionTranslation = [];
        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeOptionTranslation['languageId'] = $languageUuid;
        $localeGroupTranslation = $localeOptionTranslation;

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_OPTION_TRANSLATION,
            \hash('md5', \mb_strtolower($data['name'] . '_' . $data['group']['name'])) . ':' . $this->locale,
            $this->context
        );
        $localeOptionTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $this->convertValue($localeOptionTranslation, 'name', $data, 'name');
        $this->convertValue($localeOptionTranslation, 'position', $data, 'position', self::TYPE_INTEGER);

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PROPERTY_GROUP_TRANSLATION,
            \hash('md5', \mb_strtolower($data['group']['name'])) . ':' . $this->locale,
            $this->context
        );
        $localeGroupTranslation['id'] = $mapping['entityUuid'];
        $this->mappingIds[] = $mapping['id'];

        $this->convertValue($localeGroupTranslation, 'name', $data['group'], 'name');
        $this->convertValue($localeGroupTranslation, 'description', $data['group'], 'description');

        if ($languageUuid !== null) {
            $option['translations'][$languageUuid] = $localeOptionTranslation;
            $option['group']['translations'][$languageUuid] = $localeGroupTranslation;
        }
    }

    private function getPrice(array $priceData, float $taxRate): array
    {
        $gross = \round((float) $priceData['price'] * (1 + $taxRate / 100), $this->context->getRounding()->getDecimals());

        if (isset($priceData['currencyShortName'])) {
            $currencyMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CURRENCY,
                $priceData['currencyShortName'],
                $this->context
            );
        }
        if (!isset($currencyMapping)) {
            return [];
        }
        $this->currencyUuid = $currencyMapping['entityUuid'];
        $this->mappingIds[] = $currencyMapping['id'];

        $price = [];
        if ($this->currencyUuid !== Defaults::CURRENCY) {
            $price[] = [
                'currencyId' => Defaults::CURRENCY,
                'gross' => $gross,
                'net' => (float) $priceData['price'],
                'linked' => true,
            ];
        }

        $price[] = [
            'currencyId' => $this->currencyUuid,
            'gross' => $gross,
            'net' => (float) $priceData['price'],
            'linked' => true,
        ];

        $listPrice = (float) $priceData['pseudoprice'];
        if ($listPrice > 0) {
            $listPriceGross = \round((float) $priceData['pseudoprice'] * (1 + $taxRate / 100), $this->context->getRounding()->getDecimals());
            $price[0]['listPrice'] = [
                'currencyId' => $this->currencyUuid,
                'gross' => $listPriceGross,
                'net' => $listPrice,
                'linked' => true,
            ];
        }

        return $price;
    }

    private function getPrices(array $priceData, array $converted): array
    {
        $newData = [];
        foreach ($priceData as $price) {
            if (!isset($price['customergroup']['id'])) {
                continue;
            }

            $customerGroupMapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CUSTOMER_GROUP,
                $price['customergroup']['id'],
                $this->context
            );

            if ($customerGroupMapping === null) {
                continue;
            }
            $customerGroupUuid = $customerGroupMapping['entityUuid'];
            $this->mappingIds[] = $customerGroupMapping['id'];

            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_productPriceRule_' . $price['id'] . '_' . $price['customergroup']['id'],
                $this->context
            );
            $productPriceRuleUuid = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];

            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_' . $price['customergroup']['id'],
                $this->context
            );
            $priceRuleUuid = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];

            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_orContainer_' . $price['customergroup']['id'],
                $this->context
            );
            $orContainerUuid = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];

            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_andContainer_' . $price['customergroup']['id'],
                $this->context
            );
            $andContainerUuid = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];

            $mapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                DefaultEntities::RULE,
                'customerGroupRule_condition_' . $price['customergroup']['id'],
                $this->context
            );
            $conditionUuid = $mapping['entityUuid'];
            $this->mappingIds[] = $mapping['id'];

            $priceArray = $this->getPrice($price, $converted['tax']['taxRate']);

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
                $data['customFields'] = $this->getAttributes($price, DefaultEntities::PRODUCT_PRICE, $this->connectionName, ['id', 'priceID'], $this->context);
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
        $this->convertValue($converted, 'metaDescription', $data, 'description');
        if (isset($converted['metaDescription'])) {
            // meta description is limited to 255 characters in Shopware 6
            $converted['metaDescription'] = \mb_substr($converted['metaDescription'], 0, 255);
        }

        $language = $this->mappingService->getDefaultLanguage($this->context);
        if ($language === null) {
            return;
        }

        $locale = $language->getLocale();
        if ($locale === null || $locale->getCode() === $this->locale) {
            return;
        }

        $localeTranslation = [];

        $localeTranslation['productId'] = $converted['id'];
        $this->convertValue($localeTranslation, 'name', $originalData, 'name');
        $this->convertValue($localeTranslation, 'keywords', $originalData, 'keywords');
        $this->convertValue($localeTranslation, 'description', $originalData, 'description_long');
        $this->convertValue($localeTranslation, 'metaTitle', $originalData, 'metaTitle');
        $this->convertValue($localeTranslation, 'packUnit', $originalData['detail'], 'packunit');

        $mapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            DefaultEntities::PRODUCT_TRANSLATION,
            $this->oldProductId . ':' . $this->locale,
            $this->context
        );
        $this->mappingIds[] = $mapping['id'];

        $languageUuid = $this->mappingService->getLanguageUuid($this->connectionId, $this->locale, $this->context);
        $localeTranslation['languageId'] = $languageUuid;

        if (isset($data['attributes'])) {
            $localeTranslation['customFields'] = $this->getAttributes($data['attributes'], DefaultEntities::PRODUCT, $this->connectionName, ['id', 'articleID', 'articledetailsID'], $this->context);
        }

        if ($languageUuid !== null) {
            $converted['translations'][$languageUuid] = $localeTranslation;
        }
    }

    private function getCategoryMapping(array $categories): array
    {
        $categoryMapping = [];

        foreach ($categories as $category) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::CATEGORY,
                $category['id'],
                $this->context
            );

            if ($mapping === null) {
                continue;
            }
            $categoryMapping[] = ['id' => $mapping['entityUuid']];
            $this->mappingIds[] = $mapping['id'];
        }

        return $categoryMapping;
    }

    private function getVisibilities(array $converted, array $shops): array
    {
        $visibilities = [];

        foreach ($shops as $shop) {
            $mapping = $this->mappingService->getMapping(
                $this->connectionId,
                DefaultEntities::SALES_CHANNEL,
                $shop,
                $this->context
            );

            if ($mapping !== null) {
                $salesChannelUuid = $mapping['entityUuid'];
                $this->mappingIds[] = $mapping['id'];
                $mapping = $this->mappingService->getOrCreateMapping(
                    $this->connectionId,
                    DefaultEntities::PRODUCT_VISIBILITY,
                    $this->oldProductId . '_' . $shop,
                    $this->context
                );
                $this->mappingIds[] = $mapping['id'];
                $visibilities[] = [
                    'id' => $mapping['entityUuid'],
                    'productId' => $converted['id'],
                    'salesChannelId' => $salesChannelUuid,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ];
            }
        }

        return $visibilities;
    }
}
