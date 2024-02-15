<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\Result\ProductReader\MainCategoryShopRelationResult;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\Result\ProductReader\ProductVisibilityResult;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\Result\ProductReader\ShopCategoryRelation;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

#[Package('services-settings')]
class ProductReader extends AbstractReader
{
    private ParameterBag $productMapping;

    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        parent::__construct($connectionFactory);

        $this->productMapping = new ParameterBag();
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::PRODUCT;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetchedProducts = $this->fetchData($migrationContext);

        $this->buildIdentifierMappings($fetchedProducts);

        $resultSet = $this->appendAssociatedData(
            $this->mapData(
                $fetchedProducts,
                [],
                ['product']
            )
        );

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_articles_details')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::PRODUCT, $total);
    }

    public function getEsdConfig(): ?string
    {
        $query = $this->connection->createQueryBuilder();

        $query->select('ifnull(currentConfig.value, defaultConfig.value) as configValue');
        $query->from('s_core_config_elements', 'defaultConfig');

        $query->leftJoin('defaultConfig', 's_core_config_values', 'currentConfig', 'defaultConfig.id =  currentConfig.element_id');

        $query->where('defaultConfig.name = :configName');
        $query->setParameter('configName', 'esdKey');

        return (string) $query->executeQuery()->fetchOne();
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function fetchMainCategoryShops(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_shops', 'shop');
        $query->addSelect(['IFNULL(shop.main_id, shop.id) AS shopId', 'shop.category_id as categoryId']);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param array<mixed> $products
     *
     * @return array<mixed>
     */
    protected function appendAssociatedData(array $products): array
    {
        $categories = $this->getCategories();
        $productVisibility = $this->getProductVisibility($categories, $this->getMainCategoryShops());

        $prices = $this->getPrices();
        $media = $this->getMedia();
        $options = $this->getConfiguratorOptions();
        $esdPath = $this->getEsdConfig();
        $esdFiles = $this->getEsdFiles();

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($products as &$product) {
            $product['_locale'] = \str_replace('_', '-', $locale);
            $product['assets'] = [];

            if (isset($categories[$product['id']])) {
                $product['categories'] = $categories[$product['id']];
            }
            if (isset($prices[$product['detail']['id']])) {
                $product['prices'] = $prices[$product['detail']['id']];
            }
            if (isset($media[$product['id']][$product['detail']['id']])) {
                $product['assets'] = $media[$product['id']][$product['detail']['id']];
            }
            if (isset($media['general'][$product['id']])) {
                $generalAssets = $media['general'][$product['id']];
                $product['assets'] = \array_merge($product['assets'], $generalAssets);
            }
            if (isset($options[$product['detail']['id']])) {
                $product['configuratorOptions'] = $options[$product['detail']['id']];
            }
            if (isset($esdFiles[$product['detail']['id']])) {
                $product['esdFiles'] = \array_values($esdFiles[$product['detail']['id']]);
                foreach ($product['esdFiles'] as &$esdFile) {
                    $esdFile['path'] = $esdPath;
                }
            }

            $product['shops'] = $productVisibility->getShops($product['id']);
        }
        unset(
            $product, $categories,
            $prices, $media, $options, $esdFile
        );

        $this->productMapping->replace([]);

        return $products;
    }

    /**
     * @param array<mixed> $fetchedProducts
     */
    protected function buildIdentifierMappings(array $fetchedProducts): void
    {
        foreach ($fetchedProducts as $product) {
            $this->productMapping->set($product['product_detail.id'], $product['product.id']);
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function getConfiguratorOptions(): array
    {
        $variantIds = $this->productMapping->keys();
        $query = $this->connection->createQueryBuilder();

        $query->from('s_article_configurator_options', 'configurator_option');
        $query->addSelect('option_relation.article_id');
        $this->addTableSelection($query, 's_article_configurator_options', 'configurator_option');

        $query->leftJoin('configurator_option', 's_article_configurator_option_relations', 'option_relation', 'option_relation.option_id = configurator_option.id');

        $query->leftJoin('configurator_option', 's_article_configurator_groups', 'configurator_option_group', 'configurator_option.group_id = configurator_option_group.id');
        $this->addTableSelection($query, 's_article_configurator_groups', 'configurator_option_group');

        $query->where('option_relation.article_id IN (:ids)');
        $query->setParameter('ids', $variantIds, ArrayParameterType::INTEGER);

        $fetchedConfiguratorOptions = FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());

        return $this->mapData($fetchedConfiguratorOptions, [], ['configurator', 'option']);
    }

    /**
     * @return array<int, mixed>
     */
    private function fetchData(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_articles_details', $migrationContext->getOffset(), $migrationContext->getLimit(), ['kind']);

        $query = $this->connection->createQueryBuilder();

        $query->from('s_articles_details', 'product_detail');
        $this->addTableSelection($query, 's_articles_details', 'product_detail');

        $query->leftJoin('product_detail', 's_articles', 'product', 'product.id = product_detail.articleID');
        $this->addTableSelection($query, 's_articles', 'product');

        $query->leftJoin('product_detail', 's_core_units', 'unit', 'product_detail.unitID = unit.id');
        $this->addTableSelection($query, 's_core_units', 'unit');

        $query->leftJoin('product', 's_core_tax', 'product_tax', 'product.taxID = product_tax.id');
        $this->addTableSelection($query, 's_core_tax', 'product_tax');

        $query->leftJoin('product', 's_articles_attributes', 'product_attributes', 'product_detail.id = product_attributes.articledetailsID');
        $this->addTableSelection($query, 's_articles_attributes', 'product_attributes');

        $query->leftJoin('product', 's_articles_supplier', 'product_manufacturer', 'product.supplierID = product_manufacturer.id');
        $this->addTableSelection($query, 's_articles_supplier', 'product_manufacturer');

        $query->leftJoin('product_manufacturer', 's_media', 'product_manufacturer_media', 'product_manufacturer.img = product_manufacturer_media.path');
        $this->addTableSelection($query, 's_media', 'product_manufacturer_media');

        $query->leftJoin('product_manufacturer', 's_articles_supplier_attributes', 'product_manufacturer_attributes', 'product_manufacturer.id = product_manufacturer_attributes.supplierID');
        $this->addTableSelection($query, 's_articles_supplier_attributes', 'product_manufacturer_attributes');

        $query->where('product_detail.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->addOrderBy('product_detail.kind');
        $query->addOrderBy('product_detail.id');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return array<int|string, array<int, array<string, mixed>>>
     */
    private function getEsdFiles(): array
    {
        $variantIds = $this->productMapping->keys();
        $query = $this->connection->createQueryBuilder();

        $query->addSelect('esd.articledetailsID, esd.id, esd.file as name');
        $query->from('s_articles_esd', 'esd');

        $query->where('esd.articledetailsID IN (:ids)');
        $query->setParameter('ids', $variantIds, ArrayParameterType::INTEGER);

        $fetchedEsd = $query->executeQuery()->fetchAllAssociative();
        $result = [];

        foreach ($fetchedEsd as $esdFile) {
            $result[$esdFile['articledetailsID']][] = $esdFile;
        }

        return $result;
    }

    /**
     * @return array<string, array<int, array<string, string|null>>>
     */
    private function getCategories(): array
    {
        $iterator = $this->productMapping->getIterator();
        $productIds = \array_values(
            $iterator->getArrayCopy()
        );
        $query = $this->connection->createQueryBuilder();

        $query->from('s_articles_categories', 'product_category');

        $query->leftJoin('product_category', 's_categories', 'category', 'category.id = product_category.categoryID');
        $query->addSelect(['product_category.articleID', 'product_category.categoryID as id, category.path']);

        $query->where('product_category.articleID IN (:ids)');
        $query->setParameter('ids', $productIds, ArrayParameterType::INTEGER);

        return FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());
    }

    /**
     * @return array<int, mixed>
     */
    private function getPrices(): array
    {
        $variantIds = $this->productMapping->keys();
        $query = $this->connection->createQueryBuilder();
        $query->from('s_articles_prices', 'price');
        $query->addSelect('price.articledetailsID');
        $this->addTableSelection($query, 's_articles_prices', 'price');

        $query->leftJoin('price', 's_core_customergroups', 'price_customergroup', 'price.pricegroup = price_customergroup.groupkey');
        $this->addTableSelection($query, 's_core_customergroups', 'price_customergroup');

        $query->leftJoin('price', 's_articles_prices_attributes', 'price_attributes', 'price.id = price_attributes.priceID');
        $this->addTableSelection($query, 's_articles_prices_attributes', 'price_attributes');

        $query->leftJoin('price', 's_core_currencies', 'currency', 'currency.standard = 1');
        $query->addSelect('currency.currency as currencyShortName');

        $query->where('price.articledetailsID IN (:ids)');
        $query->setParameter('ids', $variantIds, ArrayParameterType::INTEGER);

        $fetchedPrices = FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());

        return $this->mapData($fetchedPrices, [], ['price', 'currencyShortName']);
    }

    /**
     * @return array<int, mixed>
     */
    private function getMedia(): array
    {
        $iterator = $this->productMapping->getIterator();
        $productIds = \array_values(
            $iterator->getArrayCopy()
        );

        $query = $this->connection->createQueryBuilder();
        $query->from('s_articles_img', 'asset');

        $query->addSelect('asset.articleID');
        $this->addTableSelection($query, 's_articles_img', 'asset');

        $query->leftJoin('asset', 's_articles_img', 'variantAsset', 'variantAsset.parent_id = asset.id');

        $query->leftJoin('asset', 's_articles_img_attributes', 'asset_attributes', 'asset_attributes.imageID = asset.id');
        $this->addTableSelection($query, 's_articles_img_attributes', 'asset_attributes');

        $query->leftJoin('asset', 's_media', 'asset_media', 'asset.media_id = asset_media.id');
        $this->addTableSelection($query, 's_media', 'asset_media');

        $query->leftJoin('asset_media', 's_media_attributes', 'asset_media_attributes', 'asset_media.id = asset_media_attributes.mediaID');
        $this->addTableSelection($query, 's_media_attributes', 'asset_media_attributes');

        $query->where('asset.articleID IN (:ids) AND variantAsset.id IS NULL');
        $query->setParameter('ids', $productIds, ArrayParameterType::INTEGER);

        $fetchedAssets = FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());

        $fetchedAssets = $this->mapData($fetchedAssets, [], ['asset']);
        $fetchedVariantAssets = $this->mapData($this->fetchVariantAssets(), [], ['asset', 'img', 'description', 'main', 'position']);

        $assets = [];
        foreach ($fetchedVariantAssets as $articleId => $productAssets) {
            if (!isset($assets[$articleId])) {
                $assets[$articleId] = [];
            }

            foreach ($productAssets as $productAsset) {
                if (!isset($productAsset['article_detail_id'])) {
                    continue;
                }

                if (!isset($assets[$articleId][$productAsset['article_detail_id']])) {
                    $assets[$articleId][$productAsset['article_detail_id']] = [];
                }
                $assets[$articleId][$productAsset['article_detail_id']][] = $productAsset;
            }
        }
        $assets['general'] = $fetchedAssets;
        unset($fetchedAssets, $fetchedVariantAssets);

        return $assets;
    }

    /**
     * @return array<string, array<int, array<string, string|null>>>
     */
    private function fetchVariantAssets(): array
    {
        $variantIds = $this->productMapping->keys();
        $query = $this->connection->createQueryBuilder();
        $query->from('s_articles_img', 'asset');

        $query->addSelect('parentasset.articleID');
        $this->addTableSelection($query, 's_articles_img', 'asset');
        $query->addSelect('parentasset.img as img, parentasset.description as description');
        $query->addSelect('parentasset.main as main, parentasset.position as position');

        $query->leftJoin('asset', 's_articles_img_attributes', 'asset_attributes', 'asset_attributes.imageID = asset.id');
        $this->addTableSelection($query, 's_articles_img_attributes', 'asset_attributes');

        $query->leftJoin('asset', 's_articles_img', 'parentasset', 'asset.parent_id = parentasset.id');

        $query->leftJoin('asset', 's_media', 'asset_media', 'parentasset.media_id = asset_media.id');
        $this->addTableSelection($query, 's_media', 'asset_media');

        $query->leftJoin('asset_media', 's_media_attributes', 'asset_media_attributes', 'asset_media.id = asset_media_attributes.mediaID');
        $this->addTableSelection($query, 's_media_attributes', 'asset_media_attributes');

        $query->where('asset.article_detail_id IN (:ids)');
        $query->setParameter('ids', $variantIds, ArrayParameterType::INTEGER);

        return FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());
    }

    private function getMainCategoryShops(): MainCategoryShopRelationResult
    {
        $result = new MainCategoryShopRelationResult();
        foreach ($this->fetchMainCategoryShops() as $shopCategoryRelation) {
            $result->add(new ShopCategoryRelation($shopCategoryRelation));
        }

        return $result;
    }

    /**
     * @param array<string, array<int, array<string, string|null>>> $categories
     */
    private function getProductVisibility(array $categories, MainCategoryShopRelationResult $mainCategoryShops): ProductVisibilityResult
    {
        $productVisibility = new ProductVisibilityResult();

        foreach ($categories as $productId => $productCategories) {
            foreach ($productCategories as $category) {
                foreach ($this->getParentCategoryIds($category) as $parentCategoryId) {
                    if ($mainCategoryShops->containsCategory($parentCategoryId)) {
                        $productVisibility->add((string) $productId, $mainCategoryShops->getShopIds($parentCategoryId));
                    }
                }
            }
        }

        return $productVisibility;
    }

    /**
     * @param array<string, mixed> $category
     *
     * @return array<int, string>
     */
    private function getParentCategoryIds(array $category)
    {
        if (empty($category['path'])) {
            return [];
        }

        return \array_filter(\explode('|', $category['path']));
    }
}
