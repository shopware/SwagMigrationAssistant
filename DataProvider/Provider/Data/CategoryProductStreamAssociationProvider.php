<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class CategoryProductStreamAssociationProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<CategoryCollection> $categoryRepo
     */
    public function __construct(private readonly EntityRepository $categoryRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CATEGORY_PRODUCT_STREAM_ASSOCIATION;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('productStreamId', null),
        ]));
        $criteria->addSorting(
            new FieldSorting('level'),
            new FieldSorting('autoIncrement')
        );
        $result = $this->categoryRepo->search($criteria, $context)->getEntities();

        $neededResult = [];

        foreach ($result as $item) {
            $needed = [];
            $needed['id'] = $item->getId();
            $needed['productStreamId'] = $item->getProductStreamId();
            $neededResult[] = $needed;
        }

        return $this->cleanupSearchResult($neededResult);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('productStreamId', null),
        ]));

        return $this->readTotalFromRepo($this->categoryRepo, $context, $criteria);
    }
}
