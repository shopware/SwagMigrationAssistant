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

class ProductSortingProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productSortingRepo;

    public function __construct(EntityRepositoryInterface $productSortingRepo)
    {
        $this->productSortingRepo = $productSortingRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PRODUCT_SORTING;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->productSortingRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, ['productSortingId']);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->productSortingRepo, $context);
    }
}
