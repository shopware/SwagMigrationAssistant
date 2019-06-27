<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55\Gateway\Local\Reader;

use SwagMigrationAssistant\Migration\MigrationContextInterface;

class Shopware55LocalEnvironmentReader extends Shopware55LocalAbstractReader
{
    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);
        $locale = $this->getDefaultShopLocale();

        $resultSet = [
            'defaultShopLanguage' => $locale,
            'host' => $this->getHost(),
            'additionalData' => $this->getAdditionalData(),
        ];

        return $resultSet;
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

    private function getAdditionalData(): array
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
