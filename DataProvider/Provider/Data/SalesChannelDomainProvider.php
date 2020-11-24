<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class SalesChannelDomainProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelDomainRepo;

    public function __construct(EntityRepositoryInterface $salesChannelDomainRepo)
    {
        $this->salesChannelDomainRepo = $salesChannelDomainRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::SALES_CHANNEL_DOMAIN;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('salesChannelDefaultHreflang');
        $criteria->addSorting(new FieldSorting('id'));
        $criteria->addFilter(new EqualsAnyFilter('salesChannel.typeId', [
            Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            Defaults::SALES_CHANNEL_TYPE_API,
        ]));
        $result = $this->salesChannelDomainRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('salesChannel.typeId', [
            Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            Defaults::SALES_CHANNEL_TYPE_API,
        ]));

        return $this->readTotalFromRepo($this->salesChannelDomainRepo, $context, $criteria);
    }
}
