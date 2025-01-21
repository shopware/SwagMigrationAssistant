<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\Seo\SeoUrlTemplate\SeoUrlTemplateCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class SeoUrlTemplateLookup implements ResetInterface
{
    /**
     * @param EntityRepository<SeoUrlTemplateCollection> $seoUrlTemplateRepository
     */
    public function __construct(
        private readonly EntityRepository $seoUrlTemplateRepository,
    ) {
    }

    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    public function get(
        ?string $salesChannelId,
        string $routeName,
        Context $context,
    ): ?string {
        $cacheKey = \sprintf('%s-%s', $salesChannelId, $routeName);

        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter('salesChannelId', $salesChannelId),
                    new EqualsFilter('routeName', $routeName),
                ]
            )
        );
        $criteria->setLimit(1);

        $seoUrlTemplateId = $this->seoUrlTemplateRepository->searchIds($criteria, $context)->firstId();

        $this->cache[$cacheKey] = $seoUrlTemplateId;

        return $seoUrlTemplateId;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
