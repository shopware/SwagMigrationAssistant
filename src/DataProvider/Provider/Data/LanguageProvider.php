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
use Shopware\Core\System\Language\LanguageCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class LanguageProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<LanguageCollection> $languageRepo
     */
    public function __construct(private readonly EntityRepository $languageRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::LANGUAGE;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('locale');
        $criteria->addSorting(
            new FieldSorting('parentId'), // get 'NULL' parentIds first
            new FieldSorting('id')
        );
        $result = $this->languageRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, ['languageId']);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->languageRepo, $context);
    }
}
