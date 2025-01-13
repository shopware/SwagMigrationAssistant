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
use Shopware\Core\System\NumberRange\NumberRangeCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class NumberRangeLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<NumberRangeCollection> $numberRangeRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $numberRangeRepository,
    ) {
    }

    public function get(string $type, Context $context): ?string
    {
        if (\array_key_exists($type, $this->cache)) {
            return $this->cache[$type];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(
            'number_range.type.technicalName',
            $type
        ));

        $result = $this->numberRangeRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$type] = $result;

        return $result;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
