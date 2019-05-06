<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;

class Shopware55LocalTranslationReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $fetchedTranslations = $this->fetchTranslations($this->migrationContext->getOffset(), $this->migrationContext->getLimit());

        $resultSet = $this->mapData(
            $fetchedTranslations, [], ['translation', 'locale', 'name']
        );

        return $this->cleanupResultSet($resultSet);
    }

    private function fetchTranslations(int $offset, int $limit): array
    {
        $ids = $this->fetchIdentifiers('s_core_translations', $offset, $limit);

        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_translations', 'translation');
        $this->addTableSelection($query, 's_core_translations', 'translation');

        $query->innerJoin('translation', 's_core_shops', 'shop', 'shop.id = translation.objectlanguage');
        $query->leftJoin('shop', 's_core_locales', 'locale', 'locale.id = shop.locale_id');
        $query->addSelect('REPLACE(locale.locale, "_", "-") as locale');

        $query->leftJoin('translation', 's_articles_supplier', 'manufacturer', 'translation.objecttype = "supplier" AND translation.objectkey = manufacturer.id');
        $query->addSelect('manufacturer.name');

        $query->where('translation.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $query->addOrderBy('translation.id');

        return $query->execute()->fetchAll();
    }
}
