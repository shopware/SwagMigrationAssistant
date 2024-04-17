<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class CategoryProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<CategoryCollection> $categoryRepo
     */
    public function __construct(private readonly EntityRepository $categoryRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CATEGORY;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('translations');
        $criteria->addAssociation('media.translations');
        $criteria->addAssociation('media.tags');
        $criteria->addSorting(
            new FieldSorting('level'),
            new FieldSorting('autoIncrement')
        );
        $result = $this->categoryRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, [
            // remove write protected fields
            'afterCategoryId',
            'childCount',
            'breadcrumb',
            'autoIncrement',
            'visibleChildCount',

            // media
            'mimeType',
            'fileExtension',
            'mediaTypeRaw',
            'metaData',
            'mediaType',
            'mediaId',
            'thumbnails',
            'thumbnailsRo',
            'hasFile',
            'userId', // maybe put back in, if we migrate users

            // association that is updated later
            'productStreamId',
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->categoryRepo, $context);
    }
}
