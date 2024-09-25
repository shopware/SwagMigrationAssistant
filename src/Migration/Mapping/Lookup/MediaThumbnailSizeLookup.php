<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Mapping\Lookup;

use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeCollection;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use SwagMigrationAssistant\Exception\MigrationException;
use Symfony\Contracts\Service\ResetInterface;

class MediaThumbnailSizeLookup implements ResetInterface
{
    /**
     * @var array<string, string>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<MediaThumbnailSizeCollection> $thumbnailSizeRepository
     */
    public function __construct(
        private readonly EntityRepository $thumbnailSizeRepository
    ) {}

    public function get(int $width, int $height, Context $context): ?string
    {
        $cacheKey = \sprintf('%s-%s', $width, $height);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('width', $width));
        $criteria->addFilter(new EqualsFilter('height', $height));

        $result = $this->thumbnailSizeRepository->search($criteria, $context)->getEntities()->first();
        if (!$result instanceof MediaThumbnailSizeEntity) {
            return null;
        }

        $this->cache[$cacheKey] = $result->getId();

        return $result->getId();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
