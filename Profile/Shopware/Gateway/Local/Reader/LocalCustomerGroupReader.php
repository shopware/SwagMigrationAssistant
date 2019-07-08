<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class LocalCustomerGroupReader extends LocalAbstractReader implements LocalReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::CUSTOMER_GROUP;
    }

    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);

        $fetchedCustomerGroups = $this->fetchCustomerGroups($migrationContext);
        $groupIds = array_column($fetchedCustomerGroups, 'customerGroup.id');
        $customerGroups = $this->mapData($fetchedCustomerGroups, [], ['customerGroup']);

        $fetchedDiscounts = $this->fetchCustomerGroupDiscounts($groupIds);
        $discounts = $this->mapData($fetchedDiscounts, [], ['discount']);

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($customerGroups as $key => &$customerGroup) {
            $customerGroup['_locale'] = str_replace('_', '-', $locale);
            if (isset($discounts[$customerGroup['id']])) {
                $customerGroup['discounts'] = $discounts[$customerGroup['id']];
            }
        }
        unset($customerGroup);

        return $this->cleanupResultSet($customerGroups);
    }

    private function fetchCustomerGroups(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers('s_core_customergroups', $migrationContext->getOffset(), $migrationContext->getLimit());

        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_customergroups', 'customerGroup');
        $this->addTableSelection($query, 's_core_customergroups', 'customerGroup');

        $query->leftJoin('customerGroup', 's_core_customergroups_attributes', 'customerGroup_attributes', 'customerGroup.id = customerGroup_attributes.customerGroupID');
        $this->addTableSelection($query, 's_core_customergroups_attributes', 'customerGroup_attributes');

        $query->where('customerGroup.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $query->addOrderBy('customerGroup.id');

        return $query->execute()->fetchAll();
    }

    private function fetchCustomerGroupDiscounts(array $groupIds): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_customergroups_discounts', 'discount');
        $query->addSelect(['groupID']);
        $this->addTableSelection($query, 's_core_customergroups_discounts', 'discount');

        $query->where('groupID IN (:ids)');
        $query->setParameter('ids', $groupIds, Connection::PARAM_INT_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }
}
