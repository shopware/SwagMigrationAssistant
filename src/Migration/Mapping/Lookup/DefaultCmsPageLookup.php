<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class DefaultCmsPageLookup implements ResetInterface
{
    private ?string $cache = null;

    /**
     * @param EntityRepository<CmsPageCollection> $cmsPageRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $cmsPageRepository,
    ) {
    }

    public function get(Context $context): ?string
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', 'product_list'));
        $criteria->addFilter(new EqualsFilter('locked', true));

        $cmsPage = $this->cmsPageRepository->search($criteria, $context)->getEntities()->first();

        if (!$cmsPage instanceof CmsPageEntity) {
            return null;
        }

        $this->cache = $cmsPage->getId();

        return $this->cache;
    }

    public function reset(): void
    {
        $this->cache = null;
    }
}
