<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class CustomerProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<CustomerCollection> $customerRepo
     */
    public function __construct(private readonly EntityRepository $customerRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CUSTOMER;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $result = new EntityCollection();
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use (&$result, $limit, $offset): void {
            $criteria = new Criteria();
            $criteria->setLimit($limit);
            $criteria->setOffset($offset);
            $criteria->addAssociation('addresses');
            $criteria->addSorting(new FieldSorting('id'));
            $result = $this->customerRepo->search($criteria, $context)->getEntities();
        });

        return $this->cleanupSearchResult($result, [
            'customerId',
            'tagIds',
            'autoIncrement',
            'createdById',
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->customerRepo, $context);
    }
}
