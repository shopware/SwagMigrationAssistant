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

#[Package('fundamentals@after-sales')]
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

    public function getByIso2(string $iso, Context $context): ?string
    {
        if (\array_key_exists($iso, $this->cache)) {
            return $this->cache[$iso];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));
        $criteria->setLimit(1);

        $result = $this->countryRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof CountryEntity) {
            $this->cache[$iso] = null;

            return null;
        }

        $this->cache[$iso] = $result->getId();

        return $result->getId();
    }

    public function getByIso3(string $iso3, Context $context): ?string
    {
        if (\array_key_exists($iso3, $this->cache)) {
            return $this->cache[$iso3];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso3', $iso3));
        $criteria->setLimit(1);

        $result = $this->countryRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof CountryEntity) {
            $this->cache[$iso3] = null;

            return null;
        }

        $this->cache[$iso3] = $result->getId();

        return $result->getId();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
