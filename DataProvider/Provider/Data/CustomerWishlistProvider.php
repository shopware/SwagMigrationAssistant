<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class CustomerWishlistProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $customerWishlistRepo;

    public function __construct(EntityRepositoryInterface $customerRepo)
    {
        $this->customerWishlistRepo = $customerRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CUSTOMER_WISHLIST;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('products');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->customerWishlistRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->customerWishlistRepo, $context);
    }
}
