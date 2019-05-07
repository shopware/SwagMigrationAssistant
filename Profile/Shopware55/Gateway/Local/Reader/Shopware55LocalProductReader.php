<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationNext\Migration\MigrationContextInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Shopware55LocalProductReader extends Shopware55LocalAbstractReader
{
    /**
     * @var ParameterBag
     */
    private $productMapping;

    public function __construct(Connection $connection, MigrationContextInterface $migrationContext)
    {
        parent::__construct($connection, $migrationContext);

        $this->productMapping = new ParameterBag();
    }

    public function read(): array
    {
        $fetchedProducts = $this->fetchData();

        $this->buildIdentifierMappings($fetchedProducts);

        $resultSet = $this->appendAssociatedData(
            $this->mapData(
                $fetchedProducts, [], ['product']
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

    protected function appendAssociatedData(array $products): array
    {
        $categories = $this->getCategories();
        $prices = $this->getPrices();
        $media = $this->getMedia();
        $options = $this->getConfiguratorOptions();
        $filterValues = $this->getFilterOptionValues();

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($products as $key => &$product) {
            $product['_locale'] = str_replace('_', '-', $locale);

            if (isset($categories[$product['id']])) {
                $product['categories'] = $categories[$product['id']];
            }
            if (isset($prices[$product['detail']['id']])) {
                $product['prices'] = $prices[$product['detail']['id']];
            }
            if (isset($media[$product['id']])) {
                $product['assets'] = $media[$product['id']];
            }
            if (isset($options[$product['detail']['id']])) {
                $product['configuratorOptions'] = $options[$product['detail']['id']];
            }
            if (isset($filterValues[$product['detail']['id']])) {
                $product['filters'] = $filterValues[$product['detail']['id']];
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

    private function fetchData(): array
    {
        $ids = $this->fetchIdentifiers('s_articles_details', $this->migrationContext->getOffset(), $this->migrationContext->getLimit());

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
        $query->addSelect('product_category.articleID', 'product_category.categoryID as id');
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

        $query->leftJoin('asset', 's_articles_img_attributes', 'asset_attributes', 'asset_attributes.imageID = asset.id');
        $this->addTableSelection($query, 's_articles_img_attributes', 'asset_attributes');

        $query->leftJoin('asset', 's_media', 'asset_media', 'asset.media_id = asset_media.id');
        $this->addTableSelection($query, 's_media', 'asset_media');

        $query->leftJoin('asset_media', 's_media_attributes', 'asset_media_attributes', 'asset_media.id = asset_media_attributes.mediaID');
        $this->addTableSelection($query, 's_media_attributes', 'asset_media_attributes');

        $query->where('asset.articleID IN (:ids)');
        $query->setParameter('ids', $productIds, Connection::PARAM_INT_ARRAY);

        $fetchedAssets = $query->execute()->fetchAll(\PDO::FETCH_GROUP);
        $fetchedVariantAssets = $this->fetchVariantAssets();

        foreach ($fetchedAssets as $productId => &$assets) {
            foreach ($assets as &$asset) {
                if (isset($fetchedVariantAssets[$asset['asset.id']])) {
                    $asset['children'] = $this->mapData($fetchedVariantAssets[$asset['asset.id']], [], ['asset']);
                }
            }
        }
        unset($assets, $asset);

        return $this->mapData($fetchedAssets, [], ['asset', 'children']);
    }

    private function fetchVariantAssets(): array
    {
        $variantIds = $this->productMapping->keys();
        $query = $this->connection->createQueryBuilder();
        $query->from('s_articles_img', 'asset');

        $query->addSelect('asset.parent_id');
        $this->addTableSelection($query, 's_articles_img', 'asset');

        $query->leftJoin('asset', 's_articles_img_attributes', 'asset_attributes', 'asset_attributes.imageID = asset.id');
        $this->addTableSelection($query, 's_articles_img_attributes', 'asset_attributes');

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
}
