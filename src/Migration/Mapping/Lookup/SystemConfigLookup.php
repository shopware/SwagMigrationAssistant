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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigCollection;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class SystemConfigLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<SystemConfigCollection> $systemConfigRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $systemConfigRepository,
    ) {
    }

    public function get(string $configurationKey, ?string $salesChannelId, Context $context): ?string
    {
        $cacheKey = $configurationKey . '-' . $salesChannelId;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('salesChannelId', $salesChannelId),
                    new EqualsFilter('configurationKey', $configurationKey),
                ]
            )
        );
        $criteria->setLimit(1);

        $systemConfigUuid = $this->systemConfigRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$cacheKey] = $systemConfigUuid;

        return $systemConfigUuid;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
