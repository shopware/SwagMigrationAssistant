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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class PageSystemConfigProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<SystemConfigCollection> $systemConfigRepo
     */
    public function __construct(private readonly EntityRepository $systemConfigRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PAGE_SYSTEM_CONFIG;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addFilter(
            new EqualsAnyFilter('configurationKey', [
                'core.basicInformation.contactPage',
                'core.basicInformation.shippingPaymentInfoPage',
                'core.basicInformation.privacyPage',
                'core.basicInformation.imprintPage',
                'core.basicInformation.revocationPage',
                'core.basicInformation.tosPage',
                'core.scheduled_indexers',
            ])
        );
        $criteria->addSorting(new FieldSorting('salesChannelId', FieldSorting::DESCENDING), new FieldSorting('id'));
        $result = $this->systemConfigRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsAnyFilter('configurationKey', [
                'core.basicInformation.contactPage',
                'core.basicInformation.shippingPaymentInfoPage',
                'core.basicInformation.privacyPage',
                'core.basicInformation.imprintPage',
                'core.basicInformation.revocationPage',
                'core.basicInformation.tosPage',
                'core.scheduled_indexers',
            ])
        );

        return $this->readTotalFromRepo($this->systemConfigRepo, $context, $criteria);
    }
}
