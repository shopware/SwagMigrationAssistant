<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class CrossSellingProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<ProductCrossSellingCollection> $crossSellingRepo
     */
    public function __construct(private readonly EntityRepository $crossSellingRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CROSS_SELLING;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('assignedProducts');
        $criteria->addAssociation('translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->crossSellingRepo->search($criteria, $context);

        $cleanResult = $this->cleanupSearchResult($result, [
            'crossSellingId',
            'productCrossSellingId',
        ]);

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->crossSellingRepo, $context);
    }
}
