<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactory;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\TableReaderInterface;

class Shopware55LocalTableReader implements TableReaderInterface
{
    public function read(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        $connection = ConnectionFactory::createDatabaseConnection($migrationContext);
        $query = $connection->createQueryBuilder();
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
