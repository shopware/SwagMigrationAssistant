<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class ProductMainVariantRelationProvider extends AbstractProvider
{
    public function __construct(private readonly EntityRepository $productRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::MAIN_VARIANT_RELATION;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('mainVariantId', null)]));
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->productRepo->search($criteria, $context);

        $cleanResult = $this->cleanupSearchResult($result);

        $returnValue = [];
        foreach ($cleanResult as $product) {
            $returnValue[] = [
                'id' => $product['id'],
                'mainVariantId' => $product['mainVariantId'],
            ];
        }

        return $returnValue;
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('mainVariantId', null)]));

        return $this->readTotalFromRepo($this->productRepo, $context, $criteria);
    }
}
