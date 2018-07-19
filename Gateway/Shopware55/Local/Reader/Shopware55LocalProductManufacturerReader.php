<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

use Doctrine\DBAL\Driver\PDOConnection;
use PDO;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;

class Shopware55LocalProductManufacturerReader implements Shopware55LocalReaderInterface
{
    public function supports(): string
    {
        return ProductManufacturerDefinition::getEntityName();
    }

    public function read(PDOConnection $connection): array
    {
        $sql = '
SELECT * FROM s_articles_supplier;
        ';

        return $connection->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }
}
