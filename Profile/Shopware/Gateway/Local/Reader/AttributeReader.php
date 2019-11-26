<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class AttributeReader extends AbstractReader implements ReaderInterface
{
    /**
     * @var string[]
     */
    private $supportedCustomFields = [
        DefaultEntities::CATEGORY_CUSTOM_FIELD,
        DefaultEntities::CUSTOMER_CUSTOM_FIELD,
        DefaultEntities::CUSTOMER_GROUP_CUSTOM_FIELD,
        DefaultEntities::PRODUCT_MANUFACTURER_CUSTOM_FIELD,
        DefaultEntities::ORDER_CUSTOM_FIELD,
        DefaultEntities::ORDER_DOCUMENT_CUSTOM_FIELD,
        DefaultEntities::PRODUCT_CUSTOM_FIELD,
        DefaultEntities::PRODUCT_PRICE_CUSTOM_FIELD,
    ];

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && in_array($migrationContext->getDataSet()::getEntity(), $this->supportedCustomFields, true);
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);

        if (isset($params['attribute_table'])) {
            $table = $params['attribute_table'];
            $schemaManager = $this->connection->getSchemaManager();
            if (!$schemaManager->tablesExist([$table])) {
                return [];
            }

            return $this->getAttributeConfiguration($table);
        }

        return [];
    }

    private function getAttributeConfiguration(string $table): array
    {
        $columns = $this->getTableColumns($table);
        $foreignKeys = $this->getTableForeignKeys($table);
        $columns = $this->cleanupColumns($columns, $foreignKeys);

        $attributeConfiguration = $this->connection->createQueryBuilder()
            ->select('config.column_name, config.*')
            ->from('s_attribute_configuration', 'config')
            ->where('config.table_name = :table')
            ->setParameter('table', $table)
            ->execute()
            ->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE)
        ;

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
            $name = str_replace($table . '_', '', $translation['name']);
            $field = mb_substr($translation['name'], mb_strrpos($translation['name'], '_') + 1);
            $column = mb_substr($name, 0, mb_strrpos($name, '_'));

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
                '_locale' => str_replace('_', '-', $locale),
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
        $fks = array_merge(...$fks);

        foreach ($columns as $column) {
            if ($column->getAutoincrement() === true || in_array($column->getName(), $fks, true)) {
                continue;
            }
            $result[] = $column;
        }

        return $result;
    }
}
