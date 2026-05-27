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
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('fundamentals@after-sales')]
class SalesChannelHomeCmsPageAssociationProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepo
     */
    public function __construct(private readonly EntityRepository $salesChannelRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::SALES_CHANNEL_HOME_CMS_PAGE_ASSOCIATION;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('homeCmsPageId', null),
        ]));
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->salesChannelRepo->search($criteria, $context)->getEntities();

        $neededResult = [];

        foreach ($result as $item) {
            $neededResult[] = [
                'id' => $item->getId(),
                'homeCmsPageId' => $item->getHomeCmsPageId(),
            ];
        }

        return $this->cleanupSearchResult($neededResult);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
            new EqualsFilter('homeCmsPageId', null),
        ]));

        return $this->readTotalFromRepo($this->salesChannelRepo, $context, $criteria);
    }
}
