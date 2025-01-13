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
use Shopware\Core\System\NumberRange\Aggregate\NumberRangeType\NumberRangeTypeCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class NumberRangeTypeLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<NumberRangeTypeCollection> $numberRangeTypeRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $numberRangeTypeRepository,
    ) {
    }

    public function get(string $technicalName, Context $context): ?string
    {
        if (\array_key_exists($technicalName, $this->cache)) {
            return $this->cache[$technicalName];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));

        $numberRangeTypeId = $this->numberRangeTypeRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$technicalName] = $numberRangeTypeId;

        return $numberRangeTypeId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
