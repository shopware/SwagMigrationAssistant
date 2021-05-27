<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class OrderProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepo;

    public function __construct(EntityRepositoryInterface $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::ORDER;
    }

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
        }

        return $result;
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->orderRepo, $context);
    }
}
