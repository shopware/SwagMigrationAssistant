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
use Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class TaxRuleLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<TaxRuleCollection> $taxRuleRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $taxRuleRepository,
    ) {
    }

    public function get(string $taxId, string $countryId, string $taxRuleTypeId, Context $context): ?string
    {
        $cacheKey = \sprintf('%s-%s-%s', $taxId, $countryId, $taxRuleTypeId);
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxId', $taxId));
        $criteria->addFilter(new EqualsFilter('countryId', $countryId));
        $criteria->addFilter(new EqualsFilter('taxRuleTypeId', $taxRuleTypeId));
        $criteria->setLimit(1);

        $taxRuleId = $this->taxRuleRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$cacheKey] = $taxRuleId;

        return $taxRuleId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
