<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Feature;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class CmsPageProvider extends AbstractProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $cmsPageRepo;

    public function __construct(EntityRepositoryInterface $cmsPageRepo)
    {
        $this->cmsPageRepo = $cmsPageRepo;
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
        foreach ($cleanResult as $key => $page) {
            if (isset($page['categories'])) {
                $cleanCategories = [];
                foreach ($page['categories'] as $category) {
                    $cleanCategories[] = [
                        'id' => $category['id'],
                    ];
                }
                $cleanResult[$key]['categories'] = $cleanCategories;
            }
        }

        return $cleanResult;
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();

        return $this->readTotalFromRepo($this->cmsPageRepo, $context, $criteria);
    }
}
