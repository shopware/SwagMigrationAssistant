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
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\Locale\LocaleEntity;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class LocaleLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<LocaleCollection> $localeRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $localeRepository,
    ) {
    }

    public function get(string $localeCode, Context $context): ?string
    {
        if (\array_key_exists($localeCode, $this->cache)) {
            return $this->cache[$localeCode];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $localeCode));
        $criteria->setLimit(1);

        $result = $this->localeRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof LocaleEntity) {
            $this->cache[$localeCode] = null;

            return null;
        }

        $this->cache[$localeCode] = $result->getId();

        return $result->getId();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
