<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class OrderReader extends AbstractReader
{
    /**
     * @var array<int, string>
     */
    private array $orderIds = [];

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::ORDER;
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

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_order')
            ->where('status != -1')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::ORDER, $total);
    }

    /**
     * @return array<mixed>
     */
    private function fetchOrders(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers(
            's_order',
            $migrationContext->getOffset(),
            $migrationContext->getLimit(),
            ['id'],
            ['status != -1']
        );

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

        $query->where('ordering.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->addOrderBy('ordering.id');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param array<mixed> $orders
     *
     * @return array<mixed>
     */
    private function appendAssociatedData(array $orders): array
    {
        $orderEsd = $this->getOrderEsd();
        $orderDetails = $this->getOrderDetails();
        $orderDocuments = $this->getOrderDocuments();

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($orders as &$order) {
            $order['_locale'] = \str_replace('_', '-', $locale);
            if (isset($orderDetails[$order['id']])) {
                $order['details'] = $orderDetails[$order['id']];
                if (isset($orderEsd[$order['id']])) {
                    $this->setEsd($order, $orderEsd);
                }
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

    /**
     * @return array<mixed>
     */
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
        $query->setParameter('ids', $this->orderIds, ArrayParameterType::INTEGER);

        $fetchedOrderDetails = FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());

        return $this->mapData($fetchedOrderDetails, [], ['detail']);
    }

    /**
     * @return array<mixed>
     */
    private function getOrderEsd(): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->select('esd.orderID, esd.orderdetailsID');
        $query->from('s_order_esd', 'esd');
        $this->addTableSelection($query, 's_order_esd', 'esd');

        $query->where('esd.orderID IN (:ids)');
        $query->setParameter('ids', $this->orderIds, ArrayParameterType::INTEGER);

        $query = $query->executeQuery();
        $fetchedEsd = FetchModeHelper::group($query->fetchAllAssociative());

        $result = [];
        $esdArray = $this->mapData($fetchedEsd, [], ['esd']);
        $esdConfig = $this->getEsdConfig();

        foreach ($esdArray as $key => $esdOrder) {
            foreach ($esdOrder as $esd) {
                if (isset($esd['orderdetailsID']) && !isset($result[$key][$esd['orderdetailsID']])) {
                    $esd['downloadAvailablePaymentStatus'] = $esdConfig;
                    $result[$key][$esd['orderdetailsID']] = $esd;
                }
            }
        }

        return $result;
    }

    private function getEsdConfig(): ?string
    {
        $query = $this->connection->createQueryBuilder();

        $query->select('ifnull(currentConfig.value, defaultConfig.value) as configValue');
        $query->from('s_core_config_elements', 'defaultConfig');

        $query->leftJoin('defaultConfig', 's_core_config_values', 'currentConfig', 'defaultConfig.id =  currentConfig.element_id');

        $query->where('defaultConfig.name = :esdConfigName');
        $query->setParameter('esdConfigName', 'downloadAvailablePaymentStatus');

        return $query->executeQuery()->fetchOne();
    }

    /**
     * @return array<mixed>
     */
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
        $query->setParameter('ids', $this->orderIds, ArrayParameterType::INTEGER);

        $fetchedOrderDocuments = FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());

        return $this->mapData($fetchedOrderDocuments, [], ['document']);
    }

    /**
     * @param array<mixed> $order
     * @param array<mixed> $esdArray
     */
    private function setEsd(array &$order, array $esdArray): void
    {
        if (!isset($order['id'])) {
            return;
        }

        $orderId = $order['id'];
        foreach ($order['details'] as &$detail) {
            if (!isset($detail['id'])) {
                continue;
            }

            $orderDetailId = $detail['id'];
            if (isset($esdArray[$orderId][$orderDetailId])) {
                $detail['esd'] = $esdArray[$orderId][$orderDetailId];
            }
        }
    }
}
