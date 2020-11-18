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

class CustomFieldSetProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $customFieldSetRepo;

    public function __construct(EntityRepositoryInterface $customFieldSetRepo)
    {
        $this->customFieldSetRepo = $customFieldSetRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CUSTOM_FIELD_SET;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('relations');
        $criteria->addAssociation('customFields');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->customFieldSetRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->customFieldSetRepo, $context);
    }
}
