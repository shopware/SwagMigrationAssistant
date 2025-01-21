<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Tax\TaxCollection;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class TaxLookup implements ResetInterface
{
    /**
     * @var array<int|string, string|null>
     */
    private array $cache = [];

    /**
     * @var array<string, float|null>
     */
    private array $taxRateCache = [];

    /**
     * @param EntityRepository<TaxCollection> $taxRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $taxRepository,
    ) {
    }

    public function get(float $taxRate, Context $context): ?string
    {
        $cacheKey = (string) $taxRate;

        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));
        $criteria->setLimit(1);

        $result = $this->taxRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof TaxEntity) {
            $this->cache[$cacheKey] = null;

            return null;
        }

        $this->cache[$cacheKey] = $result->getId();

        return $result->getId();
    }

    public function getByTaxRateAndName(float $taxRate, string $name, Context $context): ?string
    {
        $cacheKey = $taxRate . '-' . $name;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxRate', $taxRate));
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->setLimit(1);

        $taxRateUuid = $this->taxRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$cacheKey] = $taxRateUuid;

        return $taxRateUuid;
    }

    public function getTaxRate(string $uuid, Context $context): ?float
    {
        if (\array_key_exists($uuid, $this->taxRateCache)) {
            return $this->taxRateCache[$uuid];
        }

        $tax = $this->taxRepository->search(new Criteria([$uuid]), $context)->getEntities()->first();
        if (!$tax instanceof TaxEntity) {
            $this->taxRateCache[$uuid] = null;

            return null;
        }

        $this->taxRateCache[$uuid] = $tax->getTaxRate();

        return $this->taxRateCache[$uuid];
    }

    public function reset(): void
    {
        $this->cache = [];
        $this->taxRateCache = [];
    }
}
