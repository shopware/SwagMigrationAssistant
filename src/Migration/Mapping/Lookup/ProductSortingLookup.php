<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingCollection;
use Shopware\Core\Content\Product\SalesChannel\Sorting\ProductSortingEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class ProductSortingLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @var array<string, bool|null>
     */
    private array $lockedCache = [];

    /**
     * @param EntityRepository<ProductSortingCollection> $productSortingRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $productSortingRepository,
    ) {
    }

    public function get(string $key, Context $context): ?string
    {
        if (\array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('key', $key));
        $criteria->setLimit(1);

        $productSorting = $this->productSortingRepository->search($criteria, $context)->first();

        $productSortingUuid = null;
        $isLocked = false;

        if ($productSorting instanceof ProductSortingEntity) {
            $productSortingUuid = $productSorting->getId();
            $isLocked = $productSorting->isLocked();
        }

        $this->cache[$key] = $productSortingUuid;
        $this->lockedCache[$key] = $isLocked;

        return $productSortingUuid;
    }

    public function getIsLocked(string $key, Context $context): bool
    {
        if (\array_key_exists($key, $this->lockedCache)) {
            return $this->lockedCache[$key] ?? false;
        }

        $this->get($key, $context);

        return $this->lockedCache[$key] ?? false;
    }

    public function reset(): void
    {
        $this->cache = [];
        $this->lockedCache = [];
    }
}
