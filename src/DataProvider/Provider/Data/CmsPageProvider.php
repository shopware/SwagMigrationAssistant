<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class CmsPageProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<CmsPageCollection> $cmsPageRepo
     */
    public function __construct(private readonly EntityRepository $cmsPageRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::CMS_PAGE;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addAssociation('categories');
        $criteria->addAssociation('translations');
        $criteria->addAssociation('sections.blocks.slots.translations');
        $criteria->addSorting(new FieldSorting('id'));
        $criteria
            ->getAssociation('sections')
            ->addFilter(new EqualsFilter('locked', false))
            ->getAssociation('blocks')
            ->addFilter(new EqualsFilter('locked', false))
            ->getAssociation('slots')
            ->addFilter(new EqualsFilter('locked', false));
        $result = $this->cmsPageRepo->search($criteria, $context);

        $cleanResult = $this->cleanupSearchResult($result, [
            'pageId',
            'cmsPageId',
            'sectionId',
            'blockId',
            'cmsSlotId',

            // media
            'mimeType',
            'fileExtension',
            'fileSize',
            'mediaTypeRaw',
            'metaData',
            'mediaType',
            'mediaId',
            'thumbnails',
            'thumbnailsRo',
            'hasFile',
            'url',
            'userId', // maybe put back in, if we migrate users
        ], ['config']);

        // cleanup categories - only ids are needed
        foreach ($cleanResult as &$page) {
            $this->cleanupAssociationToOnlyContainIds($page, 'categories');
        }

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();

        return $this->readTotalFromRepo($this->cmsPageRepo, $context, $criteria);
    }
}
