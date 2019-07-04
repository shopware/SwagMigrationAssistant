<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware55\Shopware55Profile;

class LocalNewsletterRecipientReader extends LocalAbstractReader implements LocalReaderInterface
{
    public function supports(string $profileName, DataSet $dataSet): bool
    {
        return $profileName === Shopware55Profile::PROFILE_NAME && $dataSet::getEntity() === DefaultEntities::NEWSLETTER_RECIPIENT;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);

        $ids = $this->fetchIdentifiers('s_campaigns_mailaddresses', $migrationContext->getOffset(), $migrationContext->getLimit());
        $newsletterData = $this->fetchData($ids);
        $shopsByCustomer = $this->getShopsAndLocalesByCustomer($ids);
        $defaultShop = $this->getDefaultShopAndLocaleByGroupId();
        $shops = $this->getShopsAndLocalesByGroupId();

        foreach ($newsletterData as &$item) {
            if (isset($item['customer'], $shopsByCustomer[$item['id']][0]['shopId']) && $item['customer'] === '1') {
                $this->addShopAndLocaleByCustomer($item, $shopsByCustomer[$item['id']][0]);
                continue;
            }

            $this->addShopAndLocaleByGroupId($item, $defaultShop, $shops);
        }
        unset($item);

        return $this->cleanupResultSet($newsletterData);
    }

    private function addShopAndLocaleByGroupId(array &$item, array $defaultShop, array $shops): void
    {
        if (isset($defaultShop[$item['groupID']][0])) {
            $item['shopId'] = $defaultShop[$item['groupID']][0]['shopId'];
            $item['_locale'] = str_replace('_', '-', $defaultShop[$item['groupID']][0]['locale']);

            return;
        }

        if (isset($shops[$item['groupID']])) {
            $shop = $shops[$item['groupID']][0];
            $shopId = $shop['mainId'] ?? $shop['shopId'];

            $item['shopId'] = $shopId;
            $item['_locale'] = str_replace('_', '-', $shop['locale']);
        }
    }

    private function addShopAndLocaleByCustomer(array &$item, array $shop): void
    {
        $shopId = $shop['mainId'] ?? $shop['shopId'];

        $item['shopId'] = $shopId;
        $item['_locale'] = str_replace('_', '-', $shop['locale']);
    }

    private function fetchData(array $ids): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->select('newsletter.*, addresses.customer');

        $query->from('s_campaigns_mailaddresses', 'addresses');
        $query->leftJoin('addresses', 's_campaigns_maildata', 'newsletter', 'addresses.email = newsletter.email');

        $query->where('newsletter.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $query->addOrderBy('newsletter.id');

        return $query->execute()->fetchAll();
    }

    private function getDefaultShopAndLocaleByGroupId(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->addSelect('REPLACE(REGEXP_SUBSTR(config.value, \'"[0-9]*"\'), \'"\', \'\') as groupID');
        $query->addSelect('shop.id as shopId');
        $query->addSelect('locale.locale as locale');

        $query->from('s_core_config_elements', 'config');
        $query->innerJoin('config', 's_core_shops', 'shop', 'shop.default = true');
        $query->innerJoin('shop', 's_core_locales', 'locale', 'locale.id = shop.locale_id');
        $query->where('config.name = \'newsletterdefaultgroup\'');

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }

    private function getShopsAndLocalesByGroupId(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->addSelect('REPLACE(REGEXP_SUBSTR(config_values.value, \'"[0-9]*"\'), \'"\', \'\') as groupID');
        $query->addSelect('shop.id as shopId');
        $query->addSelect('shop.main_id as mainId');
        $query->addSelect('locale.locale as locale');

        $query->from('s_core_config_elements', 'config');
        $query->innerJoin('config', 's_core_config_values', 'config_values', 'config.id = config_values.element_id');
        $query->innerJoin('config', 's_core_shops', 'shop', 'shop.id = config_values.shop_id');
        $query->innerJoin('shop', 's_core_locales', 'locale', 'locale.id = shop.locale_id');
        $query->where('config.name = \'newsletterdefaultgroup\'');

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }

    private function getShopsAndLocalesByCustomer(array $ids): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->addSelect('addresses.id as addressId');
        $query->addSelect('shop.id as shopId');
        $query->addSelect('shop.main_id as mainId');
        $query->addSelect('locale.locale as locale');

        $query->from('s_campaigns_mailaddresses', 'addresses');
        $query->innerJoin('addresses', 's_user', 'users', 'users.email = addresses.email and users.accountmode = 0');
        $query->innerJoin('users', 's_core_shops', 'shop', 'shop.id = users.subshopID');
        $query->innerJoin('users', 's_core_shops', 'language', 'language.id = users.language');
        $query->innerJoin('language', 's_core_locales', 'locale', 'locale.id = language.locale_id');
        $query->where('addresses.customer = 1');
        $query->andWhere('addresses.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }
}
