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
        $sql = '
SELECT
  variant.id AS variantID,
  variant.*,
  product.*
FROM s_articles_details AS variant
INNER JOIN s_articles AS product ON product.id = variant.articleID
';
        $products = $connection->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        $ids = array_keys($products);
        $idsString = implode(', ', $ids);

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
            $products[$key]['prices'] = $price;
        }

        return $products;
    }
}
