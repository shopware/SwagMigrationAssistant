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
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class StateMachineStateLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<StateMachineStateCollection> $stateMachineStateRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $stateMachineStateRepository,
    ) {
    }

    public function get(string $technicalName, string $stateMachineTechnicalName, Context $context): ?string
    {
        $cacheKey = $technicalName . '-' . $stateMachineTechnicalName;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', $technicalName));
        $criteria->addFilter(new EqualsFilter('stateMachine.technicalName', $stateMachineTechnicalName));
        $criteria->setLimit(1);

        $stateMachineStateId = $this->stateMachineStateRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$cacheKey] = $stateMachineStateId;

        return $stateMachineStateId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
