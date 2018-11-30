<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;

class Shopware55LocalCustomerReader extends Shopware55LocalAbstractReader
{
    /**
     * @var int
     */
    const MAX_ADDRESS_COUNT = 100;

    public function read(): array
    {
        $fetchedCustomers = $this->fetchCustomers();
        $ids = array_column($fetchedCustomers, 'customer.id');

        $customers = $this->mapData($fetchedCustomers, [], ['customer']);
        $resultSet = $this->assignAssociatedData($customers, $ids);

        return $this->cleanupResultSet($resultSet);
    }

    private function fetchCustomers(): array
    {
        $ids = $this->fetchIdentifiers('s_user', $this->migrationContext->getOffset(), $this->migrationContext->getLimit());

        $query = $this->connection->createQueryBuilder();

        $query->from('s_user', 'customer');
        $this->addTableSelection($query, 's_user', 'customer');

        $query->leftJoin('customer', 's_user_attributes', 'attributes', 'customer.id = attributes.userID');
        $this->addTableSelection($query, 's_user_attributes', 'attributes');

        $query->leftJoin('customer', 's_core_customergroups', 'customer_group', 'customer.customergroup = customer_group.groupkey');
        $this->addTableSelection($query, 's_core_customergroups', 'customer_group');

        $query->leftJoin('customer', 's_core_paymentmeans', 'defaultpayment', 'customer.paymentID = defaultpayment.id');
        $this->addTableSelection($query, 's_core_paymentmeans', 'defaultpayment');

        $query->leftJoin('defaultpayment', 's_core_paymentmeans_attributes', 'defaultpayment_attributes', 'defaultpayment.id = defaultpayment_attributes.paymentmeanID');
        $this->addTableSelection($query, 's_core_paymentmeans_attributes', 'defaultpayment_attributes');

        $query->leftJoin('customer', 's_core_locales', 'customerlanguage', 'customer.language = customerlanguage.id');
        $this->addTableSelection($query, 's_core_locales', 'customerlanguage');

        $query->leftJoin('customer', 's_core_shops', 'shop', 'customer.subshopID = shop.id');
        $this->addTableSelection($query, 's_core_shops', 'shop');

        $query->where('customer.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $query->addOrderBy('customer.id');

        return $query->execute()->fetchAll();
    }

    private function assignAssociatedData(array $customers, array $ids): array
    {
        $customerAddresses = $this->fetchCustomerAdresses($ids);
        $addresses = $this->mapData($customerAddresses, [], ['address']);

        $fetchedPaymentData = $this->fetchPaymentData($ids);
        $paymentData = $this->mapData($fetchedPaymentData, [], ['paymentdata']);

        $groupIds = array_column(
            array_column($customers, 'group'),
            'id'
        );
        $fetchedDiscounts = $this->fetchCustomerGroupDiscounts($groupIds);
        $discounts = $this->mapData($fetchedDiscounts, [], ['discounts']);

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($customers as $key => &$customer) {
            $customer['_locale'] = $locale;
            if (isset($addresses[$customer['id']])) {
                $customer['addresses'] = array_slice($addresses[$customer['id']], 0, self::MAX_ADDRESS_COUNT);
            }
            if (isset($paymentData[$customer['id']])) {
                $customer['paymentdata'] = $paymentData[$customer['id']];
            }
            if (isset($discounts[$customer['group']['id']])) {
                $customer['group']['discounts'] = $discounts[$customer['group']['id']];
            }
        }
        unset($customer);

        return $customers;
    }

    private function fetchCustomerAdresses(array $ids): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_user_addresses', 'address');
        $query->addSelect('address.user_id');
        $this->addTableSelection($query, 's_user_addresses', 'address');

        $query->leftJoin('address', 's_user_addresses_attributes', 'address_attributes', 'address.id = address_attributes.address_id');
        $this->addTableSelection($query, 's_user_addresses_attributes', 'address_attributes');

        $query->leftJoin('address', 's_core_countries', 'country', 'address.country_id = country.id');
        $this->addTableSelection($query, 's_core_countries', 'country');

        $query->leftJoin('address', 's_core_countries_states', 'state', 'address.state_id = state.id');
        $this->addTableSelection($query, 's_core_countries_states', 'state');

        $query->where('address.user_id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
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

    private function fetchPaymentData(array $ids): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_core_payment_data', 'paymentdata');
        $query->addSelect('paymentdata.user_id');
        $this->addTableSelection($query, 's_core_payment_data', 'paymentdata');

        $query->where('paymentdata.user_id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP);
    }
}
