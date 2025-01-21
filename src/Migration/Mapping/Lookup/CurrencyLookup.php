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
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class CurrencyLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<CurrencyCollection> $currencyRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $currencyRepository,
    ) {
    }

    public function get(string $isoCode, Context $context): ?string
    {
        if (\array_key_exists($isoCode, $this->cache)) {
            return $this->cache[$isoCode];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $isoCode));
        $criteria->setLimit(1);

        $result = $this->currencyRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof CurrencyEntity) {
            $this->cache[$isoCode] = null;

            return null;
        }

        $this->cache[$isoCode] = $result->getId();

        return $result->getId();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
