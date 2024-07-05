<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class PromotionProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<PromotionCollection> $promotionRepo
     */
    public function __construct(private readonly EntityRepository $promotionRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PROMOTION;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('salesChannels');
        $criteria->addAssociation('discounts.discountRules');
        $criteria->addAssociation('discounts.promotionDiscountPrices');
        $criteria->addAssociation('individualCodes');
        $criteria->addAssociation('personaRules');
        $criteria->addAssociation('personaCustomers');
        $criteria->addAssociation('cartRules');
        $criteria->addAssociation('translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->promotionRepo->search($criteria, $context);
        $cleanResult = $this->cleanupSearchResult($result, ['promotionId', 'autoIncrement']);
        $this->cleanupAssociations($cleanResult);

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->promotionRepo, $context);
    }

    /**
     * @param array<mixed> $cleanResult
     */
    private function cleanupAssociations(array &$cleanResult): void
    {
        foreach ($cleanResult as &$promotion) {
            $this->cleanupAssociationToOnlyContainIds($promotion, 'personaRules');
            $this->cleanupAssociationToOnlyContainIds($promotion, 'cartRules');
            $this->cleanupAssociationToOnlyContainIds($promotion, 'personaCustomers');
        }
    }
}
