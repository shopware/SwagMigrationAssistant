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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class MediaFolderProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<MediaFolderCollection> $mediaFolderRepo
     */
    public function __construct(private readonly EntityRepository $mediaFolderRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::MEDIA_FOLDER;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addSorting(new FieldSorting('id'));
        $criteria->addAssociation('configuration.mediaThumbnailSizes');
        $criteria->addAssociation('defaultFolder');
        $result = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($criteria) {
            return $this->mediaFolderRepo->search($criteria, $context);
        });

        return $this->cleanupSearchResult($result, [
            'defaultFolderId',
            'configurationId',
            'childCount',
            'parentId', // will be entered in separate, simulated MediaFolderInheritance entity
            'useParentConfiguration',
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $context->scope(Context::SYSTEM_SCOPE, function (Context $context) {
            return $this->readTotalFromRepo($this->mediaFolderRepo, $context);
        });
    }
}
