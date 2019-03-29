<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationNext\Migration\Profile\TableReaderInterface;

class Shopware55LocalTableReader implements TableReaderInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function read(string $tableName, array $filter = []): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*');
        $query->from($tableName);

        if (!empty($filter)) {
            foreach ($filter as $property => $value) {
                $query->andWhere($property . ' = :value');
                $query->setParameter('value', $value);
            }
        }

        return $query->execute()->fetchAll();
    }
}
