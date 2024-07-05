<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\ProductStream\Aggregate\ProductStreamFilter\ProductStreamFilterCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class ProductStreamFilterInheritanceProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<ProductStreamFilterCollection> $productStreamFilterRepo
     */
    public function __construct(private readonly EntityRepository $productStreamFilterRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PRODUCT_STREAM_FILTER_INHERITANCE;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addSorting(new FieldSorting('id'));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsFilter('parentId', null),
        ]));
        $result = $this->productStreamFilterRepo->search($criteria, $context);

        $cleanResult = $this->cleanupSearchResult($result, [
            'productStreamId',
            'type',
            'field',
            'operator',
            'value',
            'parameters',
            'position',
            'customFields',
        ]);

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsFilter('parentId', null),
        ]));

        return $this->readTotalFromRepo($this->productStreamFilterRepo, $context, $criteria);
    }
}
