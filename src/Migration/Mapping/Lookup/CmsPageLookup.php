<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\Cms\Aggregate\CmsPageTranslation\CmsPageTranslationEntity;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('fundamentals@after-sales')]
class CmsPageLookup implements ResetInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<CmsPageCollection> $cmsPageRepository
     *
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $cmsPageRepository,
    ) {
    }

    /**
     * @param array<string> $names
     */
    public function getByNames(array $names, Context $context): ?string
    {
        $cacheKey = \implode('-', $names);
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addFilter(new EqualsFilter('locked', false));
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsAnyFilter('translations.name', $names),
            new EqualsAnyFilter('name', $names),
        ]));

        $cmsPageUuid = $this->filter(
            $this->cmsPageRepository->search($criteria, $context)->getEntities(),
            $names
        );

        $this->cache[$cacheKey] = $cmsPageUuid;

        return $cmsPageUuid;
    }

    /**
     * @param array<string> $names
     */
    public function getLockedByNamesAndType(array $names, string $type, Context $context): ?string
    {
        $cacheKey = \implode('-', $names) . '-' . $type;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addFilter(new EqualsFilter('locked', true));
        $criteria->addFilter(new EqualsAnyFilter('translations.name', $names));
        $criteria->addFilter(new EqualsFilter('type', $type));

        $cmsPageUuid = $this->filter(
            $this->cmsPageRepository->search($criteria, $context)->getEntities(),
            $names
        );

        $this->cache[$cacheKey] = $cmsPageUuid;

        return $cmsPageUuid;
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    /**
     * @param array<string> $names
     */
    private function filter(CmsPageCollection $cmsPages, array $names): ?string
    {
        foreach ($cmsPages as $cmsPage) {
            $translations = $cmsPage->getTranslations();
            if ($translations === null) {
                continue;
            }

            $newNames = \array_map(static function (CmsPageTranslationEntity $translation) {
                return $translation->getName();
            }, $translations->getElements());

            if (\count(\array_diff($names, $newNames)) > 0 && \count($names) === \count($newNames)) {
                continue;
            }

            return $cmsPage->getId();
        }

        return null;
    }
}
