<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class PromotionProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $promotionRepo;

    public function __construct(EntityRepositoryInterface $promotionRepo)
    {
        $this->promotionRepo = $promotionRepo;
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

    private function cleanupAssociations(array &$cleanResult): void
    {
        foreach ($cleanResult as $key => $promotion) {
            if (isset($promotion['personaRules'])) {
                $cleanPromotion = [];
                foreach ($promotion['personaRules'] as $rule) {
                    $cleanPromotion[] = [
                        'id' => $rule['id'],
                    ];
                }
                $cleanResult[$key]['personaRules'] = $cleanPromotion;
            }

            if (isset($promotion['cartRules'])) {
                $cleanPromotion = [];
                foreach ($promotion['cartRules'] as $rule) {
                    $cleanPromotion[] = [
                        'id' => $rule['id'],
                    ];
                }
                $cleanResult[$key]['cartRules'] = $cleanPromotion;
            }

            if (isset($promotion['personaCustomers'])) {
                $cleanPromotion = [];
                foreach ($promotion['personaCustomers'] as $customer) {
                    $cleanPromotion[] = [
                        'id' => $customer['id'],
                    ];
                }
                $cleanResult[$key]['personaCustomers'] = $cleanPromotion;
            }
        }
    }
}
