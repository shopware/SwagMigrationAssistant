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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\NumberRange\NumberRangeCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class NumberRangeProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<NumberRangeCollection> $numberRangeRepo
     */
    public function __construct(private readonly EntityRepository $numberRangeRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::NUMBER_RANGE;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('numberRangeSalesChannels');
        $criteria->addAssociation('state');
        $criteria->addAssociation('translations');
        $criteria->addAssociation('type');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->numberRangeRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, ['numberRangeId']);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->numberRangeRepo, $context);
    }
}
