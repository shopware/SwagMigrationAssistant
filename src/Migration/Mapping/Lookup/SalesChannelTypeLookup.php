<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Defaults;
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
     * Copy of Defaults::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE which only exists from Shopware 6.7.10.0 onwards, while this plugin supports 6.7.0.0.
     *
     * @deprecated tag:v19.0.0 - use Defaults::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE instead once shopware/core => 6.7.10.0
     */
    private const SALES_CHANNEL_TYPE_AGENTIC_COMMERCE = '5e29f9890c4d4d519a1c7f9d5c24b7c1';

    /**
     * @var array<string, bool>
     */
    private array $existsCache = [];

    /**
     * @param EntityRepository<SalesChannelTypeCollection> $salesChannelTypeRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelTypeRepository,
    ) {
    }

    /**
     * Uses the provided Sales Channel Type UUID as lookup ID
     * as these UUIDs are hard-coded and remain unchanged across systems.
     */
    public function isDefaultSalesChannelType(string $salesChannelTypeId, Context $context): bool
    {
        return \in_array($salesChannelTypeId, self::getCoreDefaultSalesChannelTypeIds(), true)
            && $this->hasSalesChannelType($salesChannelTypeId, $context);
    }

    public function hasSalesChannelType(string $salesChannelTypeId, Context $context): bool
    {
        if (\array_key_exists($salesChannelTypeId, $this->existsCache)) {
            return $this->existsCache[$salesChannelTypeId];
        }

        $criteria = new Criteria([$salesChannelTypeId]);
        $criteria->setLimit(1);

        $this->existsCache[$salesChannelTypeId] = $this->salesChannelTypeRepository->searchIds($criteria, $context)->firstId() !== null;

        return $this->existsCache[$salesChannelTypeId];
    }

    public function reset(): void
    {
        $this->existsCache = [];
    }

    /**
     * @return list<string>
     */
    private static function getCoreDefaultSalesChannelTypeIds(): array
    {
        return [
            Defaults::SALES_CHANNEL_TYPE_API,
            Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON,
            /** @phpstan-ignore classConstant.deprecated (the replacement only exists from shopware/core 6.7.10.0) */
            self::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE,
        ];
    }
}
