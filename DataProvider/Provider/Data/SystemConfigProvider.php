<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class SystemConfigProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $systemConfigRepo;

    public function __construct(EntityRepositoryInterface $systemConfigRepo)
    {
        $this->systemConfigRepo = $systemConfigRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::SYSTEM_CONFIG;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsAnyFilter('configurationKey', [
                'core.store.apiUri',
                'core.basicInformation.contactPage',
                'core.basicInformation.shippingPaymentInfoPage',
                'core.basicInformation.privacyPage',
                'core.basicInformation.imprintPage',
                'core.basicInformation.revocationPage',
                'core.basicInformation.tosPage',
                'core.scheduled_indexers',
            ])
        ]));
        $criteria->addSorting(new FieldSorting('salesChannelId', FieldSorting::DESCENDING), new FieldSorting('id'));
        $result = $this->systemConfigRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();

        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsAnyFilter('configurationKey', [
                'core.basicInformation.contactPage',
                'core.basicInformation.shippingPaymentInfoPage',
                'core.basicInformation.privacyPage',
                'core.basicInformation.imprintPage',
                'core.basicInformation.revocationPage',
                'core.basicInformation.tosPage',
                'core.scheduled_indexers',
            ])
        ]));

        return $this->readTotalFromRepo($this->systemConfigRepo, $context, $criteria);
    }
}
