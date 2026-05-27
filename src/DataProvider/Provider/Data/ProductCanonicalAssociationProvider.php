<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('fundamentals@after-sales')]
class ProductCanonicalAssociationProvider extends AbstractProvider
{
    private const BUNDLE_PRODUCT_TYPE = 'grouped_bundle';

    /**
     * @param EntityRepository<ProductCollection> $productRepo
     */
    public function __construct(private readonly EntityRepository $productRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PRODUCT_CANONICAL_ASSOCIATION;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $this->addBundleExclusionFilter($criteria);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('canonicalProductId', null),
        ]));
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->productRepo->search($criteria, $context)->getEntities();

        $neededResult = [];

        foreach ($result as $item) {
            $neededResult[] = [
                'id' => $item->getId(),
                'canonicalProductId' => $item->getCanonicalProductId(),
            ];
        }

        return $this->cleanupSearchResult($neededResult);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $this->addBundleExclusionFilter($criteria);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('canonicalProductId', null),
        ]));

        return $this->readTotalFromRepo($this->productRepo, $context, $criteria);
    }

    private function addBundleExclusionFilter(Criteria $criteria): void
    {
        if (!$this->hasTypeColumn()) {
            return;
        }

        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('type', self::BUNDLE_PRODUCT_TYPE),
        ]));
    }

    private function hasTypeColumn(): bool
    {
        return $this->productRepo->getDefinition()->getField('type') !== null;
    }
}
