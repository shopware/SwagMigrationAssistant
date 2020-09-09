<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class CategoryAssociationProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepo;

    public function __construct(EntityRepositoryInterface $categoryRepo)
    {
        $this->categoryRepo = $categoryRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CATEGORY_ASSOCIATION;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('afterCategoryId', null),
        ]));
        $criteria->addSorting(
            new FieldSorting('level'),
            new FieldSorting('autoIncrement')
        );
        $result = $this->categoryRepo->search($criteria, $context);

        $neededResult = [];

        /** @var CategoryEntity $item */
        foreach ($result->getElements() as $item) {
            $needed = [];
            $needed['id'] = $item->getId();
            $needed['afterCategoryId'] = $item->getAfterCategoryId();
            $neededResult[] = $needed;
        }

        return $this->cleanupSearchResult($neededResult);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('afterCategoryId', null),
        ]));

        return $this->readTotalFromRepo($this->categoryRepo, $context, $criteria);
    }
}
