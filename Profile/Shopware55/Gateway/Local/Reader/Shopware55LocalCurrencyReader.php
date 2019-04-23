<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

class Shopware55LocalCurrencyReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $currencies = $this->fetchData();

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($currencies as $key => &$currency) {
            $currency['_locale'] = str_replace('_', '-', $locale);
        }

        $currencies = $this->mapData($currencies, [], ['currency']);

        return $this->cleanupResultSet($currencies);
    }

    private function fetchData(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->from('s_core_currencies', 'currency');
        $this->addTableSelection($query, 's_core_currencies', 'currency');

        $query->setFirstResult($this->migrationContext->getOffset());
        $query->setMaxResults($this->migrationContext->getLimit());

        return $query->execute()->fetchAll();
    }
}
