<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Converter;

use Shopware\Core\Content\Media\Aggregate\MediaAlbum\MediaAlbumDefinition;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\System\Tax\TaxDefinition;
use Shopware\Core\System\Unit\UnitDefinition;
use SwagMigrationNext\Migration\Mapping\MappingServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConverterHelperServiceInterface;
use SwagMigrationNext\Profile\Shopware55\ConvertStruct;

class ProductConverter implements ConverterInterface
{
    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    /**
     * @var ConverterHelperServiceInterface
     */
    private $helper;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConverterHelperServiceInterface $converterHelperService
    ) {
        $this->mappingService = $mappingService;
        $this->helper = $converterHelperService;
    }

    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    /**
     * @throws ParentEntityForChildNotFoundException
     */
    public function convert(array $data): ConvertStruct
    {
        $productKind = (int) $data['detail']['kind'];
        unset($data['detail']['kind']);
        $isProductWithVariant = $data['configurator_set_id'] !== null;

        if ($productKind === 1 && $isProductWithVariant) {
            return $this->convertMainProduct($data);
        }

        if ($productKind === 2 && $isProductWithVariant) {
            return $this->convertVariantProduct($data);
        }

        $converted = $this->getUuidForProduct($data);
        $converted = $this->getProductData($data, $converted);

        if (empty($data)) {
            $data = null;
        }

        return new ConvertStruct($converted, $data);
    }

    private function convertMainProduct(array $data): ConvertStruct
    {
        $containerUuid = $this->mappingService->createNewUuid(
            ProductDefinition::getEntityName() . '_container',
            $data['id']
        );
        $converted['id'] = $containerUuid;
        unset($data['id'], $data['detail']['articleID']);

        $converted = $this->getProductData($data, $converted);

        $converted['children'][] = $converted;
        $converted['children'][0]['id'] = $this->mappingService->createNewUuid(
            ProductDefinition::getEntityName(),
            $data['detail']['id']
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
            ProductDefinition::getEntityName() . '_container',
            $data['id']
        );

        if ($parentUuid === null) {
            throw new ParentEntityForChildNotFoundException(ProductDefinition::getEntityName());
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
            ProductDefinition::getEntityName(),
            $data['detail']['id']
        );
        unset($data['detail']['id'], $data['detail']['articleID'], $data['id']);

        return $converted;
    }

    private function getProductData(array &$data, array $converted): array
    {
        // Legacy data which don't need a mapping or there is no equivalent field
        unset(
            $data['datum'],
            $data['changetime'],
            $data['crossbundlelook'],
            $data['mode'],
            $data['main_detail_id'],
            $data['available_from'],
            $data['available_to'],

            // TODO check how to handle these
            $data['configurator_set_id'],
            $data['pricegroupID'],
            $data['pricegroupActive'],
            $data['filtergroupID'],
            $data['template'],
            $data['detail']['ordernumber'],
            $data['detail']['additionaltext'],
            $data['attributes']
        );

        $converted['manufacturer'] = $this->getManufacturer($data['manufacturer']);
        unset($data['manufacturer'], $data['supplierID']);

        $converted['tax'] = $this->getTax($data['tax']);
        unset($data['tax'], $data['taxID']);

        $converted['unit'] = $this->getUnit($data['unit']);
        unset($data['unit'], $data['detail']['unitID']);

        $converted['price'] = $this->getPrice($data['prices'][0], $converted['tax']['taxRate']);
        $converted['priceRules'] = $this->getPriceRules($data['prices'], $converted);
        unset($data['prices']);

        $converted['media'] = $this->getAssets($data['assets'], $converted);
        unset($data['assets']);

        $this->helper->convertValue($converted, 'active', $data, 'active', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'name', $data, 'name');
        $this->helper->convertValue($converted, 'description', $data, 'description');
        $this->helper->convertValue($converted, 'descriptionLong', $data, 'description_long');
        $this->helper->convertValue($converted, 'metaTitle', $data, 'metaTitle');
        $this->helper->convertValue($converted, 'keywords', $data, 'keywords');
        $this->helper->convertValue($converted, 'minDeliveryTime', $data, 'shippingtime', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'isCloseout', $data, 'laststock', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'pseudoSales', $data, 'pseudosales', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'markAsTopseller', $data, 'topseller', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'allowNotification', $data, 'notification', $this->helper::TYPE_BOOLEAN);

        $this->helper->convertValue($converted, 'supplierNumber', $data['detail'], 'suppliernumber');
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
        $this->helper->convertValue($converted, 'packUnit', $data['detail'], 'packunit');
        $this->helper->convertValue($converted, 'releaseDate', $data['detail'], 'releasedate');
        $this->helper->convertValue($converted, 'shippingFree', $data['detail'], 'shippingfree', $this->helper::TYPE_BOOLEAN);
        $this->helper->convertValue($converted, 'minDeliveryTime', $data['detail'], 'shippingtime', $this->helper::TYPE_INTEGER);
        $this->helper->convertValue($converted, 'purchasePrice', $data['detail'], 'purchaseprice', $this->helper::TYPE_FLOAT);
        if (empty($data['detail'])) {
            unset($data['detail']);
        }

        return $converted;
    }

    private function getManufacturer(array $manufacturerData): array
    {
        $newData['id'] = $this->mappingService->createNewUuid(
            ProductManufacturerDefinition::getEntityName(),
            $manufacturerData['id']
        );

        $this->helper->convertValue($newData, 'name', $manufacturerData, 'name');
        $this->helper->convertValue($newData, 'link', $manufacturerData, 'link');
        $this->helper->convertValue($newData, 'description', $manufacturerData, 'description');
        $this->helper->convertValue($newData, 'metaTitle', $manufacturerData, 'meta_title');
        $this->helper->convertValue($newData, 'metaDescription', $manufacturerData, 'meta_description');
        $this->helper->convertValue($newData, 'metaKeywords', $manufacturerData, 'meta_keywords');

        return $newData;
    }

    private function getTax(array $taxData): array
    {
        return [
            'id' => $this->mappingService->createNewUuid(
                TaxDefinition::getEntityName(),
                $taxData['id']
            ),
            'taxRate' => (float) $taxData['tax'],
            'name' => $taxData['description'],
        ];
    }

    private function getUnit(array $unitData): array
    {
        return [
            'id' => $this->mappingService->createNewUuid(
                UnitDefinition::getEntityName(),
                $unitData['id']
            ),
            'shortCode' => $unitData['unit'],
            'name' => $unitData['description'],
        ];
    }

    private function getAssets(array $assets, array $converted): array
    {
        $media = [];
        foreach ($assets as $asset) {
            $newProductMedia = [];
            $newProductMedia['id'] = $this->mappingService->createNewUuid(
                ProductMediaDefinition::getEntityName(),
                $asset['id']
            );
            $newProductMedia['productId'] = $converted['id'];
            $this->helper->convertValue($newProductMedia, 'isCover', $asset, 'main', $this->helper::TYPE_BOOLEAN);
            $this->helper->convertValue($newProductMedia, 'position', $asset, 'position', $this->helper::TYPE_INTEGER);

            $newMedia = [];
            $newMedia['id'] = $this->mappingService->createNewUuid(
                MediaDefinition::getEntityName(),
                $asset['media']['id'],
                ['uri' => $asset['media']['uri']]
            );
            $this->helper->convertValue($newMedia, 'name', $asset['media'], 'name');
            $this->helper->convertValue($newMedia, 'description', $asset['media'], 'description');

            $newAlbum = [];
            $newAlbum['id'] = $this->mappingService->createNewUuid(
                MediaAlbumDefinition::getEntityName(),
                $asset['media']['album']['id']
            );
            $this->helper->convertValue($newAlbum, 'name', $asset['media']['album'], 'name');
            $this->helper->convertValue($newAlbum, 'position', $asset['media']['album'], 'position', $this->helper::TYPE_INTEGER);
//            $this->helper->convertValue($newAlbum, 'createThumbnails', $asset['media']['album']['settings'], 'create_thumbnails', $this->helper::TYPE_BOOLEAN);
            $newAlbum['createThumbnails'] = false; // TODO: Remove, needs a bugfix in the core
            $this->helper->convertValue($newAlbum, 'thumbnailSize', $asset['media']['album']['settings'], 'thumbnail_size');
            $this->helper->convertValue($newAlbum, 'icon', $asset['media']['album']['settings'], 'icon');
            $this->helper->convertValue($newAlbum, 'thumbnailHighDpi', $asset['media']['album']['settings'], 'thumbnail_high_dpi', $this->helper::TYPE_BOOLEAN);
            $this->helper->convertValue($newAlbum, 'thumbnailQuality', $asset['media']['album']['settings'], 'thumbnail_quality', $this->helper::TYPE_INTEGER);
            $this->helper->convertValue($newAlbum, 'thumbnailHighDpiQuality', $asset['media']['album']['settings'], 'thumbnail_high_dpi_quality', $this->helper::TYPE_INTEGER);

            $newMedia['album'] = $newAlbum;
            $newProductMedia['media'] = $newMedia;
            $media[] = $newProductMedia;
        }

        return $media;
    }

    private function getPrice(array $priceData, float $taxRate): array
    {
        return [
            'gross' => (float) $priceData['price'] * (1 + $taxRate / 100),
            'net' => (float) $priceData['price'],
        ];
    }

    private function getPriceRules(array $priceData, array $converted): array
    {
        $newData = [];
        foreach ($priceData as $price) {
            $newData[] = [
                'productId' => $converted['id'],
                'currencyId' => Defaults::CURRENCY,
                'rule' => [
                    'id' => $this->mappingService->createNewUuid(
                        RuleDefinition::getEntityName(),
                        $price['pricegroup']
                    ),
                    'name' => $price['pricegroup'],
                    'priority' => 0,
                    'payload' => new AndRule(),
                ],
                'price' => $this->getPrice($price, $converted['tax']['taxRate']),
                'quantityStart' => (int) $price['from'],
                'quantityEnd' => $price['to'] !== 'beliebig' ? (int) $price['to'] : null,
            ];
        }

        return $newData;
    }
}
