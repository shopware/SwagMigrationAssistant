<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class MediaFolderInheritanceProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<MediaFolderCollection> $mediaFolderRepo
     */
    public function __construct(private readonly EntityRepository $mediaFolderRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::MEDIA_FOLDER_INHERITANCE;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addSorting(new FieldSorting('id'));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('parentId', null)]));
        $result = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($criteria) {
            return $this->mediaFolderRepo->search($criteria, $context);
        });

        return $this->cleanupSearchResult($result, [
            'defaultFolder',
            'defaultFolderId',
            'configuration',
            'configurationId',
            'childCount',
            'name',
            'customFields',
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [new EqualsFilter('parentId', null)]));

        return $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($criteria) {
            return $this->readTotalFromRepo($this->mediaFolderRepo, $context, $criteria);
        });
    }
}
