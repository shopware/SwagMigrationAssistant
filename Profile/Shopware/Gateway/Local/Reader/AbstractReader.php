<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;

abstract class AbstractReader implements ReaderInterface
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

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return false;
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        return null;
    }

    /**
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    protected function setConnection(MigrationContextInterface $migrationContext): void
    {
        if ($this->connection instanceof Connection && $this->connection->isConnected()) {
            return;
        }

        $connection = $this->connectionFactory->createDatabaseConnection($migrationContext);

        if ($connection === null) {
            return;
        }

        $this->connection = $connection;
    }

    protected function addTableSelection(QueryBuilder $query, string $table, string $tableAlias): void
    {
        $columns = $this->connection->getSchemaManager()->listTableColumns($table);

        foreach ($columns as $column) {
            $selection = \str_replace(
                ['#tableAlias#', '#column#'],
                [$tableAlias, $column->getName()],
                '`#tableAlias#`.`#column#` as `#tableAlias#.#column#`'
            );

            $query->addSelect($selection);
        }
    }

    /**
     * @psalm-suppress MissingParamType
     */
    protected function buildArrayFromChunks(array &$array, array $path, string $fieldKey, $value): void
    {
        $key = \array_shift($path);

        if (empty($key)) {
            $array[$fieldKey] = $value;
        } elseif (empty($path)) {
            $array[$key][$fieldKey] = $value;
        } else {
            if (!isset($array[$key]) || !\is_array($array[$key])) {
                $array[$key] = [];
            }
            $this->buildArrayFromChunks($array[$key], $path, $fieldKey, $value);
        }
    }

    protected function cleanupResultSet(array &$data): array
    {
        foreach ($data as $key => &$value) {
            if (\is_array($value)) {
                if (empty(\array_filter($value))) {
                    unset($data[$key]);

                    continue;
                }

                $this->cleanupResultSet($value);

                if (empty(\array_filter($value))) {
                    unset($data[$key]);

                    continue;
                }
            }
        }

        return $data;
    }

    protected function fetchIdentifiers(string $table, int $offset = 0, int $limit = 250, array $orderBy = []): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->select('id');
        $query->from($table);

        $query->setFirstResult($offset);
        $query->setMaxResults($limit);

        foreach ($orderBy as $order) {
            $query->addOrderBy($order);
        }

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        return $query->fetchAll(\PDO::FETCH_COLUMN);
    }

    protected function getDefaultShopLocale(): string
    {
        $query = $this->connection->createQueryBuilder()
            ->select('locale.locale')
            ->from('s_core_locales', 'locale')
            ->innerJoin('locale', 's_core_shops', 'shop', 'locale.id = shop.locale_id')
            ->where('shop.default = 1')
            ->andWhere('shop.active = 1')
            ->execute();

        $defaultShopLocale = '';
        if ($query instanceof ResultStatement) {
            $defaultShopLocale = (string) $query->fetchColumn();
        }

        return $defaultShopLocale;
    }

    protected function mapData(array $data, array $result = [], array $pathsToRemove = []): array
    {
        foreach ($data as $key => $value) {
            if (\is_numeric($key)) {
                $result[$key] = $this->mapData($value, [], $pathsToRemove);
            } else {
                $paths = \explode('.', $key);
                $fieldKey = $paths[\count($paths) - 1];
                $chunks = \explode('_', $paths[0]);

                if (!empty($pathsToRemove)) {
                    $chunks = \array_diff($chunks, $pathsToRemove);
                }
                $this->buildArrayFromChunks($result, $chunks, $fieldKey, $value);
            }
        }

        return $result;
    }

    protected function getDataSetEntity(MigrationContextInterface $migrationContext): ?string
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return null;
        }

        return $dataSet::getEntity();
    }
}
