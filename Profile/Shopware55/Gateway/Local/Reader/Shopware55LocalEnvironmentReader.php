<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader;

class Shopware55LocalEnvironmentReader extends Shopware55LocalAbstractReader
{
    private const TABLES_TO_COUNT = [
        'products' => 's_articles_details',
        'customers' => 's_user',
        'categories' => 's_categories',
        'assets' => 's_media',
        'orders' => 's_order',
        'shops' => 's_core_shops',
        'shoppingWorlds' => 's_emotion',
        'translations' => 's_core_translations',
        'customerGroups' => 's_core_customergroups',
        'configuratorOptions' => 's_article_configurator_options',
        'numberRanges' => 's_order_number',
        'currencies' => 's_core_currencies',
    ];

    public function read(): array
    {
        $locale = $this->getDefaultShopLocale();

        $resultSet = [
            'defaultShopLanguage' => $locale,
            'host' => $this->getHost(),
            'structure' => $this->getShopStructure(),
        ];

        foreach (self::TABLES_TO_COUNT as $key => $table) {
            if ($key === 'categories') {
                $resultSet[$key] = $this->getCategoryCount();
                continue;
            }
            $resultSet[$key] = $this->getTableCount($table);
        }

        return $resultSet;
    }

    private function getCategoryCount(): int
    {
        $query = $this->connection->createQueryBuilder();

        return (int) $query->select('COUNT(id)')
            ->from('s_categories')
            ->where('path IS NOT NULL')
            ->andWhere('parent IS NOT NULL')
            ->execute()
            ->fetchColumn();
    }

    private function getHost(): string
    {
        $query = $this->connection->createQueryBuilder();

        return (string) $query->select('shop.host')
            ->from('s_core_shops', 'shop')
            ->where('shop.default = 1')
            ->andWhere('shop.active = 1')
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }

    private function getTableCount(string $table): int
    {
        $querybuilder = $this->connection->createQueryBuilder();

        return (int) $querybuilder->select('COUNT(id)')
            ->from($table)
            ->execute()
            ->fetchColumn();
    }

    private function getShopStructure(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.id as identifier');
        $this->addTableSelection($query, 's_core_shops', 'shop');

        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $this->addTableSelection($query, 's_core_locales', 'locale');

        $query->orderBy('shop.main_id');

        $fetchedShops = $query->execute()->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
        $shops = $this->mapData($fetchedShops, [], ['shop']);

        foreach ($shops as $key => &$shop) {
            if (!empty($shop['main_id'])) {
                $shops[$shop['main_id']]['children'][] = $shop;
                unset($shops[$key]);
            }
        }

        return array_values($shops);
    }
}
