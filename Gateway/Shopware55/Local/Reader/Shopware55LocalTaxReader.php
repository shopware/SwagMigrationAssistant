<?php declare(strict_types=1);

namespace SwagMigrationNext\Gateway\Shopware55\Local\Reader;

use Doctrine\DBAL\Driver\PDOConnection;
use PDO;
use Shopware\Core\System\Tax\TaxDefinition;

class Shopware55LocalTaxReader implements Shopware55LocalReaderInterface
{
    public function supports(): string
    {
        return TaxDefinition::getEntityName();
    }

    public function read(PDOConnection $connection): array
    {
        $sql = '
SELECT tax.* FROM s_core_tax AS tax;
        ';

        return $connection->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }
}
