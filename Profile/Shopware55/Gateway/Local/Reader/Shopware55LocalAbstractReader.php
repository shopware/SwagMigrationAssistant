<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ReaderInterface;
use SwagMigrationAssistant\Profile\Shopware55\Gateway\Connection\ConnectionFactoryInterface;

abstract class Shopware55LocalAbstractReader implements ReaderInterface
{
    /**
     * @var ConnectionFactoryInterface
     */
    protected $connectionFactory;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    protected function setConnection(MigrationContextInterface $migrationContext): void
    {
        $this->connection = $this->connectionFactory->createDatabaseConnection($migrationContext);
    }

    protected function addTableSelection(QueryBuilder $query, string $table, string $tableAlias): void
    {
        $columns = $this->connection->getSchemaManager()->listTableColumns($table);

        /** @var Column $column */
        foreach ($columns as $column) {
            $selection = str_replace(
                ['#tableAlias#', '#column#'],
                [$tableAlias, $column->getName()],
                '`#tableAlias#`.`#column#` as `#tableAlias#.#column#`'
            );

            $query->addSelect($selection);
        }
    }

    protected function buildArrayFromChunks(array &$array, array $path, string $fieldKey, $value): void
    {
        $key = array_shift($path);

        if (empty($key)) {
            $array[$fieldKey] = $value;
        } elseif (empty($path)) {
            $array[$key][$fieldKey] = $value;
        } else {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $this->buildArrayFromChunks($array[$key], $path, $fieldKey, $value);
        }
    }

    protected function cleanupResultSet(array &$data): array
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                if (empty(array_filter($value))) {
                    unset($data[$key]);

                    continue;
                }

                $this->cleanupResultSet($value);

                if (empty(array_filter($value))) {
                    unset($data[$key]);

                    continue;
                }
            }
        }

        return $data;
    }

    protected function fetchIdentifiers(string $table, int $offset = 0, int $limit = 250): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->select('id');
        $query->from($table);
        $query->addOrderBy('id');

        $query->setFirstResult($offset);
        $query->setMaxResults($limit);

        return $query->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function getDefaultShopLocale()
    {
        $query = $this->connection->createQueryBuilder();

        return $query->select('locale.locale')
            ->from('s_core_locales', 'locale')
            ->innerJoin('locale', 's_core_shops', 'shop', 'locale.id = shop.locale_id')
            ->where('shop.default = 1')
            ->andWhere('shop.active = 1')
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }

    protected function mapData(array $data, array $result = [], array $pathsToRemove = []): array
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $result[$key] = $this->mapData($value, [], $pathsToRemove);
            } else {
                $paths = explode('.', $key);
                $fieldKey = $paths[count($paths) - 1];
                $chunks = explode('_', $paths[0]);

                if (!empty($pathsToRemove)) {
                    $chunks = array_diff($chunks, $pathsToRemove);
                }
                $this->buildArrayFromChunks($result, $chunks, $fieldKey, $value);
            }
        }

        return $result;
    }
}
