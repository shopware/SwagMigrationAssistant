<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\TableReaderInterface;

class TableReader implements TableReaderInterface
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function read(MigrationContextInterface $migrationContext, string $tableName, array $filter = []): array
    {
        $connection = $this->connectionFactory->createDatabaseConnection($migrationContext);
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
