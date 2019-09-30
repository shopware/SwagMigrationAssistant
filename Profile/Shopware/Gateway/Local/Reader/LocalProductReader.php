<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class LocalProductReader extends LocalAbstractReader implements LocalReaderInterface
{
    /**
     * @var ParameterBag
     */
    private $productMapping;

    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        parent::__construct($connectionFactory);

        $this->productMapping = new ParameterBag();
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::PRODUCT;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
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

    public function getFilterOptionValues(): array
    {
        $variantIds = $this->productMapping->keys();

        $query = $this->connection->createQueryBuilder();

        $query->from('s_filter_articles', 'filter');
        $query->leftJoin('filter', 's_articles_details', 'details', 'details.articleID = filter.articleID');
        $query->addSelect('details.id');

        $query->leftJoin('filter', 's_filter_values', 'filter_values', 'filter.valueID = filter_values.id');
        $this->addTableSelection($query, 's_filter_values', 'filter_values');

        $query->leftJoin('filter_values', 's_filter_options', 'filter_values_option', 'filter_values_option.id = filter_values.optionID');
        $this->addTableSelection($query, 's_filter_options', 'filter_values_option');

        $query->where('details.id IN (:ids)');

        $query->setParameter('ids', $variantIds, Connection::PARAM_INT_ARRAY);

        $fetchedFilterOptionValues = $query->execute()->fetchAll(\PDO::FETCH_GROUP);

        return $this->mapData($fetchedFilterOptionValues, [], ['filter', 'values']);
    }

    /**
     * @return array
     */
    public function fetchShopsByCategories(array $categories)
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_categories', 'category');
        $query->addSelect('category.id');

        $query->innerJoin('category', 's_core_shops', 'shop', 'category.id = shop.category_id');
        $query->addSelect('IFNULL(shop.main_id, shop.id) AS "id"');

        $query->where('category.id IN (:ids)');
        $query->setParameter('ids', $categories, Connection::PARAM_INT_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }

    protected function appendAssociatedData(array $products): array
    {
        $categories = $this->getCategories();
        $topMostParentIds = $this->getTopMostParentIds($categories);
        $shops = $this->getShops($topMostParentIds);
        $prices = $this->getPrices();
        $media = $this->getMedia();
        $options = $this->getConfiguratorOptions();
        $filterValues = $this->getFilterOptionValues();

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($products as $key => &$product) {
            $product['_locale'] = str_replace('_', '-', $locale);
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
                $product['assets'] = array_merge($product['assets'], $generalAssets);
            }
            if (isset($options[$product['detail']['id']])) {
                $product['configuratorOptions'] = $options[$product['detail']['id']];
            }
            if (isset($filterValues[$product['detail']['id']])) {
                $product['filters'] = $filterValues[$product['detail']['id']];
            }
            if (isset($shops[$product['id']])) {
                $product['shops'] = array_values($shops[$product['id']]);
            }
        }
        unset(
            $product, $categories,
            $prices, $media, $options
        );

        $this->productMapping->replace([]);

        return $products;
    }

    protected function buildIdentifierMappings(array $fetchedProducts): void
    {
        foreach ($fetchedProducts as $product) {
            $this->productMapping->set($product['product_detail.id'], $product['product.id']);
        }
    }

    private function fetchData(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_articles_details', $migrationContext->getOffset(), $migrationContext->getLimit());

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
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $query->addOrderBy('product_detail.kind');
        $query->addOrderBy('product_detail.id');

        return $query->execute()->fetchAll();
    }

    private function getCategories(): array
    {
        $productIds = array_values(
            $this->productMapping->getIterator()->getArrayCopy()
        );
        $query = $this->connection->createQueryBuilder();

        $query->from('s_articles_categories', 'product_category');

        $query->leftJoin('product_category', 's_categories', 'category', 'category.id = product_category.categoryID');
        $query->addSelect('product_category.articleID', 'product_category.categoryID as id, category.path');

        $query->where('product_category.articleID IN (:ids)');
        $query->setParameter('ids', $productIds, Connection::PARAM_INT_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }

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
        $query->setParameter('ids', $variantIds, Connection::PARAM_INT_ARRAY);

        $fetchedPrices = $query->execute()->fetchAll(\PDO::FETCH_GROUP);

        return $this->mapData($fetchedPrices, [], ['price', 'currencyShortName']);
    }

    private function getMedia(): array
    {
        $productIds = array_values(
            $this->productMapping->getIterator()->getArrayCopy()
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
        $query->setParameter('ids', $productIds, Connection::PARAM_INT_ARRAY);

        $fetchedAssets = $this->mapData($query->execute()->fetchAll(\PDO::FETCH_GROUP), [], ['asset']);
        $fetchedVariantAssets = $this->mapData($this->fetchVariantAssets(), [], ['asset', 'img', 'description']);

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

    private function fetchVariantAssets(): array
    {
        $variantIds = $this->productMapping->keys();
        $query = $this->connection->createQueryBuilder();
        $query->from('s_articles_img', 'asset');

        $query->addSelect('parentasset.articleID');
        $this->addTableSelection($query, 's_articles_img', 'asset');
        $query->addSelect('parentasset.img as img, parentasset.description as description');

        $query->leftJoin('asset', 's_articles_img_attributes', 'asset_attributes', 'asset_attributes.imageID = asset.id');
        $this->addTableSelection($query, 's_articles_img_attributes', 'asset_attributes');

        $query->leftJoin('asset', 's_articles_img', 'parentasset', 'asset.parent_id = parentasset.id');

        $query->leftJoin('asset', 's_media', 'asset_media', 'parentasset.media_id = asset_media.id');
        $this->addTableSelection($query, 's_media', 'asset_media');

        $query->leftJoin('asset_media', 's_media_attributes', 'asset_media_attributes', 'asset_media.id = asset_media_attributes.mediaID');
        $this->addTableSelection($query, 's_media_attributes', 'asset_media_attributes');

        $query->where('asset.article_detail_id IN (:ids)');
        $query->setParameter('ids', $variantIds, Connection::PARAM_INT_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }

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
        $query->setParameter('ids', $variantIds, Connection::PARAM_INT_ARRAY);
        $fetchedConfiguratorOptions = $query->execute()->fetchAll(\PDO::FETCH_GROUP);

        return $this->mapData($fetchedConfiguratorOptions, [], ['configurator', 'option']);
    }

    private function getShops(array $topMostCategoriesByProduct): array
    {
        $productToCategory = [];
        $ids = [];
        foreach ($topMostCategoriesByProduct as $productKey => $product) {
            foreach ($product as $category) {
                if (!isset($ids[$category])) {
                    $ids[$category] = $category;
                }

                $key = $productKey . '_' . $category;
                if (!isset($productToCategory[$key])) {
                    $productToCategory[$key] = [
                        'productId' => $productKey,
                        'categoryId' => $category,
                    ];
                }
            }
        }

        $shops = $this->fetchShopsByCategories($ids);

        $ids = [];
        foreach ($productToCategory as $content) {
            $productId = $content['productId'];
            $categoryId = $content['categoryId'];

            if (isset($shops[$categoryId])) {
                foreach ($shops[$categoryId] as $shop) {
                    $shopId = $shop['id'];

                    if (!isset($ids[$productId][$shopId])) {
                        $ids[$productId][$shopId] = $shopId;
                    }
                }
            }
        }

        return $ids;
    }

    private function getTopMostParentIds(array $categories): array
    {
        $ids = [];
        foreach ($categories as $productKey => $product) {
            foreach ($product as $key => $category) {
                if (empty($category['path'])) {
                    continue;
                }
                $parentCategoryIds = array_values(
                    array_filter(explode('|', (string) $category['path']))
                );
                $topMostParent = end($parentCategoryIds);
                if (!isset($ids[$productKey]) || !in_array($topMostParent, $ids[$productKey], true)) {
                    $ids[$productKey][$topMostParent] = $topMostParent;
                }
            }
        }

        return $ids;
    }
}
