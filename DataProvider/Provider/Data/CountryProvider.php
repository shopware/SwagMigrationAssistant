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

class CountryProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $countryRepo;

    public function __construct(EntityRepositoryInterface $countryRepo)
    {
        $this->countryRepo = $countryRepo;
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::COUNTRY;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->countryRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, ['countryId']);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->countryRepo, $context);
    }
}
