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
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Symfony\Contracts\Service\ResetInterface;

#[Package('services-settings')]
class CountryLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<CountryCollection> $countryRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $countryRepository,
    ) {
    }

    public function get(string $iso, string $iso3, Context $context): ?string
    {
        $cacheKey = \sprintf('%s-%s', $iso, $iso3);

        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));
        $criteria->addFilter(new EqualsFilter('iso3', $iso3));
        $criteria->setLimit(1);

        $result = $this->countryRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof CountryEntity) {
            $this->cache[$cacheKey] = null;

            return null;
        }

        $this->cache[$cacheKey] = $result->getId();

        return $result->getId();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
