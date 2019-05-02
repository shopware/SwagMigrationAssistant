<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

class Shopware55LocalSalesChannelReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $fetchedSalesChannels = $this->fetchData();
        $salesChannels = $this->mapData($fetchedSalesChannels, [], ['shop', 'locale', 'currency']);

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($salesChannels as $key => &$salesChannel) {
            $salesChannel['locale'] = str_replace('_', '-', $salesChannel['locale']);
            $salesChannel['_locale'] = str_replace('_', '-', $locale);
            if (!empty($salesChannel['main_id'])) {
                $salesChannels[$salesChannel['main_id']]['children'][] = $salesChannel;
                unset($salesChannels[$key]);
            }
        }
        $salesChannels = array_values($salesChannels);

        return $this->cleanupResultSet($salesChannels);
    }

    private function fetchData()
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.id as identifier');
        $this->addTableSelection($query, 's_core_shops', 'shop');

        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $query->addSelect('locale.locale');

        $query->leftJoin('shop', 's_core_currencies', 'currency', 'shop.currency_id = currency.id');
        $query->addSelect('currency.currency');

        $query->orderBy('shop.main_id');

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
    }
}
