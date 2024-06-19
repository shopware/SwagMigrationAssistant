<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\AsciiStringType;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\DBAL\Types\DateIntervalType;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeImmutableType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\Types;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
abstract class AttributeReader extends AbstractReader
{
    private const BUILTIN_TYPES_MAP = [
        AsciiStringType::class => Types::ASCII_STRING,
        BigIntType::class => Types::BIGINT,
        BinaryType::class => Types::BINARY,
        BlobType::class => Types::BLOB,
        BooleanType::class => Types::BOOLEAN,
        DateType::class => Types::DATE_MUTABLE,
        DateImmutableType::class => Types::DATE_IMMUTABLE,
        DateIntervalType::class => Types::DATEINTERVAL,
        DateTimeType::class => Types::DATETIME_MUTABLE,
        DateTimeImmutableType::class => Types::DATETIME_IMMUTABLE,
        DateTimeTzType::class => Types::DATETIMETZ_MUTABLE,
        DateTimeTzImmutableType::class => Types::DATETIMETZ_IMMUTABLE,
        DecimalType::class => Types::DECIMAL,
        FloatType::class => Types::FLOAT,
        GuidType::class => Types::GUID,
        IntegerType::class => Types::INTEGER,
        JsonType::class => Types::JSON,
        SimpleArrayType::class => Types::SIMPLE_ARRAY,
        SmallIntType::class => Types::SMALLINT,
        StringType::class => Types::STRING,
        TextType::class => Types::TEXT,
        TimeType::class => Types::TIME_MUTABLE,
        TimeImmutableType::class => Types::TIME_IMMUTABLE,
    ];

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $table = $this->getAttributeTable();

        return $this->getAttributeConfiguration($table);
    }

    abstract protected function getAttributeTable(): string;

    /**
     * @return list<array{name: string, type: string, _locale: string, configuration: array<string, string|mixed|null>|null}>
     */
    private function getAttributeConfiguration(string $table): array
    {
        $columns = $this->getTableColumns($table);
        $foreignKeys = $this->getTableForeignKeys($table);
        $columns = $this->cleanupColumns($columns, $foreignKeys);

        $query = $this->connection->createQueryBuilder()
            ->select('config.column_name, config.*')
            ->from('s_attribute_configuration', 'config')
            ->where('config.table_name = :table')
            ->setParameter('table', $table);

        /** @var array<string, array<string, string|mixed|null>> $attributeConfiguration */
        $attributeConfiguration = FetchModeHelper::groupUnique($query->executeQuery()->fetchAllAssociative());

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
        )->fetchAllAssociative();

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

        foreach ($columns as $column) {
            $columnData = [
                'name' => $column->getName(),
                'type' => self::BUILTIN_TYPES_MAP[\get_class($column->getType())],
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
     * @return array<Column>
     */
    private function getTableColumns(string $table): array
    {
        return $this->connection->createSchemaManager()->listTableColumns($table);
    }

    /**
     * @return array<ForeignKeyConstraint>
     */
    private function getTableForeignKeys(string $table): array
    {
        return $this->connection->createSchemaManager()->listTableForeignKeys($table);
    }

    /**
     * @param array<Column> $columns
     * @param array<ForeignKeyConstraint> $foreignKeys
     *
     * @return list<Column>
     */
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
