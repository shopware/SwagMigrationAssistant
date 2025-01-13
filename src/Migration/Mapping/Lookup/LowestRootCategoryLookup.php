<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class LowestRootCategoryLookup implements ResetInterface
{
    private ?string $cache = null;

    /**
     * @param EntityRepository<CategoryCollection> $categoryRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $categoryRepository,
    ) {
    }

    public function get(Context $context): ?string
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', null));

        $searchResult = $this->categoryRepository->search($criteria, $context);
        if ($searchResult->getTotal() === 0) {
            return null;
        }

        $result = $searchResult->getEntities()->sortByPosition()->last();
        if (!$result instanceof CategoryEntity) {
            return null;
        }

        $this->cache = $result->getId();

        return $this->cache;
    }

    public function reset(): void
    {
        $this->cache = null;
    }
}
