<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

abstract class AttributeReader extends AbstractReader
{
    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $table = $this->getAttributeTable();

        return $this->getAttributeConfiguration($table);
    }

    abstract protected function getAttributeTable(): string;

    private function getAttributeConfiguration(string $table): array
    {
        $columns = $this->getTableColumns($table);
        $foreignKeys = $this->getTableForeignKeys($table);
        $columns = $this->cleanupColumns($columns, $foreignKeys);

        $query = $this->connection->createQueryBuilder()
            ->select('config.column_name, config.*')
            ->from('s_attribute_configuration', 'config')
            ->where('config.table_name = :table')
            ->setParameter('table', $table)
            ->execute();

        $attributeConfiguration = [];
        if ($query instanceof ResultStatement) {
            $attributeConfiguration = $query->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
        }

        $sql = <<<SQL
SELECT s.*, l.locale
FROM s_core_snippets s
LEFT JOIN s_core_locales l ON s.localeID = l.id
WHERE namespace = 'backend/attribute_columns'
AND name LIKE :table
SQL;

        $attributeConfigTranslations = $this->connection->executeQuery(
            $sql,
            [
                'pos' => $table,
                'table' => $table . '%',
            ]
        )->fetchAll();

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        // extract field translations and add them to config
        foreach ($attributeConfigTranslations as $translation) {
            $name = \str_replace($table . '_', '', $translation['name']);
            $nameStrPos = (int) \mb_strrpos($name, '_');
            $column = \mb_substr($name, 0, $nameStrPos);

            $translationStrPos = (int) \mb_strrpos($translation['name'], '_');
            $field = \mb_substr($translation['name'], $translationStrPos + 1);

            if (!isset($attributeConfiguration[$column]['translations'][$field])) {
                $attributeConfiguration[$column]['translations'][$field] = [];
            }
            $attributeConfiguration[$column]['translations'][$field][$translation['locale']] = $translation['value'];
        }

        $resultSet = [];

        /** @var Column $column */
        foreach ($columns as $column) {
            $columnData = [
                'name' => $column->getName(),
                'type' => $column->getType()->getName(),
                '_locale' => \str_replace('_', '-', $locale),
                'configuration' => null,
            ];

            if (isset($attributeConfiguration[$column->getName()])) {
                $columnData['configuration'] = $attributeConfiguration[$column->getName()];
            }
            $resultSet[] = $columnData;
        }

        return $resultSet;
    }

    /**
     * @return Column[]
     */
    private function getTableColumns(string $table): array
    {
        return $this->connection->getSchemaManager()->listTableColumns($table);
    }

    /**
     * @return ForeignKeyConstraint[]
     */
    private function getTableForeignKeys(string $table): array
    {
        return $this->connection->getSchemaManager()->listTableForeignKeys($table);
    }

    private function cleanupColumns(array $columns, array $foreignKeys): array
    {
        $result = [];
        $fks = [];

        foreach ($foreignKeys as $foreignKey) {
            $fks[] = $foreignKey->getLocalColumns();
        }

        if ($fks !== []) {
            $fks = \array_merge(...$fks);
        }

        foreach ($columns as $column) {
            if ($column->getAutoincrement() === true || \in_array($column->getName(), $fks, true)) {
                continue;
            }
            $result[] = $column;
        }

        return $result;
    }
}
