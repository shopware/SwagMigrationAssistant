<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class OrderProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<OrderCollection> $orderRepo
     */
    public function __construct(private readonly EntityRepository $orderRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::ORDER;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('deliveries');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('tags');
        $criteria->addAssociation('stateMachineState.stateMachine');
        $criteria->addAssociation('deliveries.stateMachineState.stateMachine');
        $criteria->addAssociation('deliveries.shippingOrderAddress');
        $criteria->addAssociation('transactions.stateMachineState.stateMachine');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->orderRepo->search($criteria, $context);

        $result = $this->cleanupSearchResult($result, [
            'shippingTotal',
            'orderDate',
            'amountTotal',
            'amountNet',
            'orderDeliveryPositions',
            'autoIncrement',
        ], ['payload', 'taxRules', 'calculatedTaxes']);

        foreach ($result as &$row) {
            unset(
                $row['taxStatus'],
                $row['positionPrice']
            );

            // ToDo MIG-902: properly migrate this association
            if (!empty($row['lineItems'])) {
                foreach ($row['lineItems'] as &$lineItem) {
                    unset($lineItem['promotionId']);
                }
            }
        }

        return $result;
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->orderRepo, $context);
    }
}
