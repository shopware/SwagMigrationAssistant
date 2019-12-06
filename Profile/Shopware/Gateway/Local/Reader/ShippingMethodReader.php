<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

class ShippingMethodReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $migrationContext->getDataSet()::getEntity() === DefaultEntities::SHIPPING_METHOD;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->setConnection($migrationContext);
        $ids = $this->fetchIdentifiers('s_premium_dispatch', $migrationContext->getOffset(), $migrationContext->getLimit());
        $fetchedShippingMethods = $this->fetchShippingMethods($ids);
        $fetchedShippingCosts = $this->fetchShippingCosts($ids);

        $resultSet = $this->mapData(
            $fetchedShippingMethods,
            [],
            ['dispatch']
        );

        $locale = $this->getDefaultShopLocale();
        foreach ($resultSet as &$item) {
            if (isset($fetchedShippingCosts[$item['id']])) {
                $item['shippingCosts'] = $fetchedShippingCosts[$item['id']];
            }

            $item['_locale'] = str_replace('_', '-', $locale);
        }

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_premium_dispatch')
            ->execute()
            ->fetchColumn();

        return new TotalStruct(DefaultEntities::SHIPPING_METHOD, $total);
    }

    private function fetchShippingMethods(array $shippingMethodIds): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_premium_dispatch', 'dispatch');
        $this->addTableSelection($query, 's_premium_dispatch', 'dispatch');

        $query->leftJoin('dispatch', 's_core_shops', 'shop', 'dispatch.multishopID = shop.id');
        $this->addTableSelection($query, 's_core_shops', 'shop');

        $query->leftJoin('dispatch', 's_core_customergroups', 'customerGroup', 'dispatch.customergroupID = customerGroup.id');
        $this->addTableSelection($query, 's_core_customergroups', 'customerGroup');

        $query->where('dispatch.id IN (:ids)');
        $query->setParameter('ids', $shippingMethodIds, Connection::PARAM_STR_ARRAY);

        $query->addOrderBy('dispatch.id');

        return $query->execute()->fetchAll();
    }

    private function fetchShippingCosts(array $shippingMethodIds): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_premium_shippingcosts', 'shippingcosts');
        $query->addSelect('shippingcosts.dispatchID as dispatchId');
        $this->addTableSelection($query, 's_premium_shippingcosts', 'shippingcosts');

        $query->leftJoin('shippingcosts', 's_core_currencies', 'currency', 'currency.standard = 1');
        $query->addSelect('currency.currency as currencyShortName');

        $query->where('shippingcosts.dispatchID IN (:ids)');
        $query->setParameter('ids', $shippingMethodIds, Connection::PARAM_STR_ARRAY);

        $query->orderBy('shippingcosts.from');

        $fetchedShippingCosts = $query->execute()->fetchAll(\PDO::FETCH_GROUP);

        return $this->mapData($fetchedShippingCosts, [], ['shippingcosts', 'currencyShortName']);
    }
}
