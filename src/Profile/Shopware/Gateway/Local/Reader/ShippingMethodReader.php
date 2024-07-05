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
class ShippingMethodReader extends AbstractReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::SHIPPING_METHOD;
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
        $shippingCountries = $this->fetchShippingCountries($ids);
        $paymentMethods = $this->fetchPaymentMethods($ids);
        $excludedCategories = $this->fetchExcludedCategories($ids);

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
            if (isset($shippingCountries[$item['id']])) {
                $item['shippingCountries'] = $shippingCountries[$item['id']];
            }
            if (isset($paymentMethods[$item['id']])) {
                $item['paymentMethods'] = \array_column($paymentMethods[$item['id']], 'paymentID');
            }
            if (isset($excludedCategories[$item['id']])) {
                $item['excludedCategories'] = \array_column($excludedCategories[$item['id']], 'categoryID');
            }

            $item['_locale'] = \str_replace('_', '-', $locale);
        }

        return $this->cleanupResultSet($resultSet);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        $total = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_premium_dispatch')
            ->executeQuery()
            ->fetchOne();

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

        $query->leftJoin('dispatch', 's_core_tax', 'tax', 'dispatch.tax_calculation = tax.id');
        $this->addTableSelection($query, 's_core_tax', 'tax');

        $query->where('dispatch.id IN (:ids)');
        $query->setParameter('ids', $shippingMethodIds, ArrayParameterType::STRING);

        $query->addOrderBy('dispatch.id');

        return $query->executeQuery()->fetchAllAssociative();
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
        $query->setParameter('ids', $shippingMethodIds, ArrayParameterType::STRING);

        $query->orderBy('shippingcosts.from');

        $fetchedShippingCosts = FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());

        return $this->mapData($fetchedShippingCosts, [], ['shippingcosts', 'currencyShortName']);
    }

    private function fetchShippingCountries(array $shippingMethodIds): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_premium_dispatch_countries', 'shippingcountries');
        $query->addSelect('shippingcountries.dispatchID, shippingcountries.countryID');

        $query->innerJoin('shippingcountries', 's_core_countries', 'country', 'country.id = shippingcountries.countryID');
        $query->addSelect('country.countryiso, country.iso3');

        $query->where('shippingcountries.dispatchID IN (:ids)');
        $query->setParameter('ids', $shippingMethodIds, ArrayParameterType::STRING);
        $query->orderBy('shippingcountries.dispatchID, shippingcountries.countryID');

        return FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());
    }

    private function fetchPaymentMethods(array $shippingMethodIds): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_premium_dispatch_paymentmeans', 'paymentMethods');
        $query->addSelect('paymentMethods.dispatchID, paymentMethods.paymentID');

        $query->where('paymentMethods.dispatchID IN (:ids)');
        $query->setParameter('ids', $shippingMethodIds, ArrayParameterType::STRING);
        $query->orderBy('paymentMethods.dispatchID, paymentMethods.paymentID');

        return FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());
    }

    private function fetchExcludedCategories(array $shippingMethodIds): array
    {
        $query = $this->connection->createQueryBuilder();

        $query->from('s_premium_dispatch_categories', 'categories');
        $query->addSelect('categories.dispatchID, categories.categoryID');

        $query->where('categories.dispatchID IN (:ids)');
        $query->setParameter('ids', $shippingMethodIds, ArrayParameterType::STRING);
        $query->orderBy('categories.dispatchID, categories.categoryID');

        return FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());
    }
}
