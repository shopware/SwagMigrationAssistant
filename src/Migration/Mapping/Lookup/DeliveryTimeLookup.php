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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\DeliveryTime\DeliveryTimeCollection;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use SwagMigrationAssistant\Exception\MigrationException;
use Symfony\Contracts\Service\ResetInterface;

class DeliveryTimeLookup implements ResetInterface
{
    /**
     * @var array<string, string>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<DeliveryTimeCollection> $deliveryTimeRepository
     */
    public function __construct(
        private readonly EntityRepository $deliveryTimeRepository
    ) {}

    public function get(int $minValue, int $maxValue, string $unit, string $name, Context $context): string
    {
        $cacheKey = \sprintf('%d-%d-%s', $minValue, $maxValue, $unit);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('min', $minValue));
        $criteria->addFilter(new EqualsFilter('max', $maxValue));
        $criteria->addFilter(new EqualsFilter('unit', $unit));
        $criteria->setLimit(1);

        $result = $this->deliveryTimeRepository->searchIds($criteria, $context);

        $deliveryTimeUuid = $result->firstId();

        if ($deliveryTimeUuid === null) {
            $deliveryTimeUuid = Uuid::isValid($name) ? $name : Uuid::randomHex();
        }

        $this->cache[$cacheKey] = $deliveryTimeUuid;

        return $deliveryTimeUuid;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
