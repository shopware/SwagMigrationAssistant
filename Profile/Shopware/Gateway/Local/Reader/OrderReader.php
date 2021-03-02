<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class OrderReader extends AbstractReader
{
    /**
     * @var array
     */
    private $orderIds;

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::ORDER;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $fetchedOrders = $this->fetchOrders($migrationContext);

        $this->orderIds = \array_column($fetchedOrders, 'ordering.id');

        $resultSet = $this->appendAssociatedData(
            $this->mapData(
                $fetchedOrders,
                [],
                ['ordering']
            )
        );

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $query = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_order')
            ->where('status != -1')
            ->execute();

        $total = 0;
        if ($query instanceof ResultStatement) {
            $total = (int) $query->fetchColumn();
        }

        return new TotalStruct(DefaultEntities::ORDER, $total);
    }

    private function fetchOrders(MigrationContextInterface $migrationContext): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_order', 'ordering');
        $this->addTableSelection($query, 's_order', 'ordering');

        $query->leftJoin('ordering', 's_order_attributes', 'attributes', 'ordering.id = attributes.orderID');
        $this->addTableSelection($query, 's_order_attributes', 'attributes');

        $query->leftJoin('ordering', 's_user', 'customer', 'customer.id = ordering.userID');
        $this->addTableSelection($query, 's_user', 'customer');

        $query->leftJoin('ordering', 's_core_states', 'orderstatus', 'orderstatus.group = "state" AND ordering.status = orderstatus.id');
        $this->addTableSelection($query, 's_core_states', 'orderstatus');

        $query->leftJoin('ordering', 's_core_states', 'paymentstatus', 'paymentstatus.group = "payment" AND ordering.cleared = paymentstatus.id');
        $this->addTableSelection($query, 's_core_states', 'paymentstatus');

        $query->leftJoin('ordering', 's_order_billingaddress', 'billingaddress', 'ordering.id = billingaddress.orderID');
        $this->addTableSelection($query, 's_order_billingaddress', 'billingaddress');

        $query->leftJoin('billingaddress', 's_order_billingaddress_attributes', 'billingaddress_attributes', 'billingaddress.id = billingaddress_attributes.billingID');
        $this->addTableSelection($query, 's_order_billingaddress_attributes', 'billingaddress_attributes');

        $query->leftJoin('billingaddress', 's_core_countries', 'billingaddress_country', 'billingaddress.countryID = billingaddress_country.id');
        $this->addTableSelection($query, 's_core_countries', 'billingaddress_country');

        $query->leftJoin('billingaddress', 's_core_countries_states', 'billingaddress_state', 'billingaddress.stateID = billingaddress_state.id');
        $this->addTableSelection($query, 's_core_countries_states', 'billingaddress_state');

        $query->leftJoin('ordering', 's_order_shippingaddress', 'shippingaddress', 'ordering.id = shippingaddress.orderID');
        $this->addTableSelection($query, 's_order_shippingaddress', 'shippingaddress');

        $query->leftJoin('shippingaddress', 's_order_shippingaddress_attributes', 'shippingaddress_attributes', 'shippingaddress.id = shippingaddress_attributes.shippingID');
        $this->addTableSelection($query, 's_order_shippingaddress_attributes', 'shippingaddress_attributes');

        $query->leftJoin('shippingaddress', 's_core_countries', 'shippingaddress_country', 'shippingaddress.countryID = shippingaddress_country.id');
        $this->addTableSelection($query, 's_core_countries', 'shippingaddress_country');

        $query->leftJoin('shippingaddress', 's_core_countries_states', 'shippingaddress_state', 'shippingaddress.stateID = shippingaddress_state.id');
        $this->addTableSelection($query, 's_core_countries_states', 'shippingaddress_state');

        $query->leftJoin('ordering', 's_core_paymentmeans', 'payment', 'payment.id = ordering.paymentID');
        $this->addTableSelection($query, 's_core_paymentmeans', 'payment');

        $query->leftJoin('ordering', 's_core_shops', 'languageshop', 'languageshop.id = ordering.language');
        $query->leftJoin('languageshop', 's_core_locales', 'language', 'language.id = languageshop.locale_id');
        $query->addSelect('language.locale AS \'ordering.locale\'');

        $query->where('ordering.status != -1');
        $query->setFirstResult($migrationContext->getOffset());
        $query->setMaxResults($migrationContext->getLimit());
        $query->addOrderBy('ordering.id');

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        return $query->fetchAll();
    }

    private function appendAssociatedData(array $orders): array
    {
        $orderDetails = $this->getOrderDetails();
        $orderDocuments = $this->getOrderDocuments();

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($orders as &$order) {
            $order['_locale'] = \str_replace('_', '-', $locale);
            if (isset($orderDetails[$order['id']])) {
                $order['details'] = $orderDetails[$order['id']];
            }
            if (isset($orderDocuments[$order['id']])) {
                $order['documents'] = $orderDocuments[$order['id']];
            }
            if (isset($order['locale'])) {
                $order['locale'] = \str_replace('_', '-', $order['locale']);
            }
        }

        return $orders;
    }

    private function getOrderDetails(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_order_details', 'detail');
        $query->select('detail.orderID');
        $this->addTableSelection($query, 's_order_details', 'detail');

        $query->leftJoin('detail', 's_order_details_attributes', 'attributes', 'detail.id = attributes.detailID');
        $this->addTableSelection($query, 's_order_details_attributes', 'attributes');

        $query->leftJoin('detail', 's_core_tax', 'tax', 'tax.id = detail.taxID');
        $this->addTableSelection($query, 's_core_tax', 'tax');

        $query->where('detail.orderID IN (:ids)');
        $query->setParameter('ids', $this->orderIds, Connection::PARAM_INT_ARRAY);

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        $fetchedOrderDetails = $query->fetchAll(\PDO::FETCH_GROUP);

        return $this->mapData($fetchedOrderDetails, [], ['detail']);
    }

    private function getOrderDocuments(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_order_documents', 'document');
        $query->select('document.orderID');
        $this->addTableSelection($query, 's_order_documents', 'document');

        $query->leftJoin('document', 's_order_documents_attributes', 'attributes', 'document.id = attributes.documentID');
        $this->addTableSelection($query, 's_order_documents_attributes', 'attributes');

        $query->leftJoin('document', 's_core_documents', 'documenttype', 'document.type = documenttype.id');
        $this->addTableSelection($query, 's_core_documents', 'documenttype');

        $query->where('document.orderID IN (:ids)');
        $query->setParameter('ids', $this->orderIds, Connection::PARAM_INT_ARRAY);

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        $fetchedOrderDocuments = $query->fetchAll(\PDO::FETCH_GROUP);

        return $this->mapData($fetchedOrderDocuments, [], ['document']);
    }
}
