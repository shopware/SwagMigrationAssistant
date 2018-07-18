<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

use Doctrine\DBAL\Driver\PDOConnection;
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
SELECT product.*, supplier.name as `supplier.name`, tax.tax AS `tax.rate`, tax.description AS `tax.name`
FROM s_articles AS product
INNER JOIN s_articles_supplier AS supplier ON supplier.id = product.`supplierID`
INNER JOIN s_core_tax as tax ON tax.id = product.taxID
';
        $products = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return ['data' => $products];
    }
}
