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
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class CountryStateLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<CountryStateCollection> $countryStateRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $countryStateRepository,
    ) {
    }

    public function get(string $countryIso, string $countryStateCode, Context $context): ?string
    {
        $cacheKey = \sprintf('%s-%s', $countryIso, $countryStateCode);

        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', $countryIso . '-' . $countryStateCode));
        $criteria->addFilter(new EqualsFilter('country.iso', $countryIso));
        $criteria->setLimit(1);

        $countryStateUuid = $this->countryStateRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$cacheKey] = $countryStateUuid;

        return $countryStateUuid;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
