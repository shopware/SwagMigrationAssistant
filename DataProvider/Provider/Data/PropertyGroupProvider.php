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

class PropertyGroupProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $propertyGroupRepo;

    public function __construct(EntityRepositoryInterface $propertyGroupRepo)
    {
        $this->propertyGroupRepo = $propertyGroupRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::PROPERTY_GROUP;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addAssociation('options');
        $criteria->addAssociation('options.translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->propertyGroupRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->propertyGroupRepo, $context);
    }
}
