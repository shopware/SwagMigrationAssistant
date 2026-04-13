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
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class SalesChannelTypeLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<SalesChannelTypeCollection> $salesChannelTypeRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelTypeRepository,
    ) {
    }

    public function get(string $salesChannelTypeId, Context $context): ?string
    {
        if (\array_key_exists($salesChannelTypeId, $this->cache)) {
            return $this->cache[$salesChannelTypeId];
        }

        $criteria = new Criteria([$salesChannelTypeId]);
        $criteria->setLimit(1);

        return $this->cache[$salesChannelTypeId] = $this->salesChannelTypeRepository->searchIds($criteria, $context)->firstId();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
