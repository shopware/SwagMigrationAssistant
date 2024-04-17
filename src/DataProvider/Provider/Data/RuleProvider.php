<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Rule\RuleCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class RuleProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<RuleCollection> $ruleRepo
     */
    public function __construct(private readonly EntityRepository $ruleRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::RULE;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('conditions');
        $criteria->getAssociation('conditions')->addSorting(new FieldSorting('createdAt'));
        $result = $this->ruleRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, ['payload']);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->ruleRepo, $context);
    }
}
