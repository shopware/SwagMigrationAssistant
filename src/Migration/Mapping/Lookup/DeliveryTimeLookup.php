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
use Shopware\Core\System\DeliveryTime\DeliveryTimeCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class DeliveryTimeLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<DeliveryTimeCollection> $deliveryTimeRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $deliveryTimeRepository,
    ) {
    }

    public function get(int $minValue, int $maxValue, string $unit, Context $context): ?string
    {
        $cacheKey = \sprintf('%d-%d-%s', $minValue, $maxValue, $unit);
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('min', $minValue));
        $criteria->addFilter(new EqualsFilter('max', $maxValue));
        $criteria->addFilter(new EqualsFilter('unit', $unit));
        $criteria->setLimit(1);

        $deliveryTimeUuid = $this->deliveryTimeRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$cacheKey] = $deliveryTimeUuid;

        return $deliveryTimeUuid;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
