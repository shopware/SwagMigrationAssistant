<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

use Doctrine\DBAL\Driver\PDOConnection;
use PDO;
use Shopware\Core\Content\Product\ProductDefinition;

class Shopware55LocalProductReader implements Shopware55LocalReaderInterface
{
    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    public function read(PDOConnection $connection): array
    {
        $variantSql = '
SELECT
  variant.id AS group_variantID,
  variant.id AS variantID,
  variant.*,
  product.*
FROM s_articles_details AS variant
INNER JOIN s_articles AS product ON product.id = variant.articleID
';
        $details = $connection->query($variantSql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        $ids = [];
        foreach ($details as $key => $detail) {
            $ids[] = $key;
            $products[$key]['product'] = $detail;
            $products[$key]['tax'] = [];
            $products[$key]['product_manufacturer'] = [];
        }
        $idsString = implode(', ', $ids);

        $manufacturerSql = sprintf('
SELECT variant.id, tax.*
FROM s_articles_details AS variant
INNER JOIN s_articles AS product ON variant.articleID = product.id
INNER JOIN s_core_tax AS tax ON tax.id = product.taxId
WHERE variant.id IN (%s)
        ', $idsString);

        $taxes = $connection->query($manufacturerSql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        $ids = [];
        foreach ($taxes as $key => $tax) {
            $ids[] = $key;
            $products[$key]['tax'] = $tax;
        }
        array_unique($products[$key]['tax']);

        $manufacturerSql = sprintf('
SELECT variant.id, supplier.*
FROM s_articles_details AS variant
INNER JOIN s_articles AS product ON variant.articleID = product.id
INNER JOIN s_articles_supplier AS supplier ON supplier.id = product.supplierID
WHERE variant.id IN (%s)
        ', $idsString);

        $manufacturers = $connection->query($manufacturerSql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        foreach ($manufacturers as $key => $manufacturer) {
            $products[$key]['product_manufacturer'] = $manufacturer;
        }

        $pricesSql = sprintf('
SELECT
  prices.articledetailsID,
  prices.*
FROM s_articles_prices AS prices
WHERE prices.articledetailsID IN (%s)
      AND pricegroup = \'%s\'
', $idsString, 'EK');

        $prices = $connection->query($pricesSql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        foreach ($prices as $key => $price) {
            $products[$key]['product']['prices'] = $price;
        }

        $pricesSql = sprintf('
SELECT
  prices.articledetailsID,
  prices.*
FROM s_articles_prices AS prices
WHERE prices.articledetailsID IN (%s)
', $idsString);

        $prices = $connection->query($pricesSql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        foreach ($prices as $key => $price) {
            $products[$key]['product_price_rule'] = $price;
        }

        return $products;
    }
}
