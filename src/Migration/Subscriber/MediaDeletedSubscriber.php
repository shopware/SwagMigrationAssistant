<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Subscriber;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Media\MediaEvents;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

#[Package('services-settings')]
class MediaDeletedSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<SwagMigrationMediaFileCollection> $mediaFileRepository
     */
    public function __construct(private readonly EntityRepository $mediaFileRepository)
    {
    }

    /**
     * Due to the order in which media file entries get written, which
     * is before the related media is written, we cannot use a foreign
     * key constraint for deletion and need to delete the media file entries
     * manually.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MediaEvents::MEDIA_DELETED_EVENT => 'onMediaDelete',
        ];
    }

    public function onMediaDelete(EntityDeletedEvent $event): void
    {
        if ($event->getEntityName() !== MediaDefinition::ENTITY_NAME) {
            return;
        }
        $context = $event->getContext();
        $deletedMediaIds = $event->getIds();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $deletedMediaIds));

        $result = $this->mediaFileRepository->searchIds($criteria, $context);
        $mediaFileIds = $result->getIds();

        if (empty($mediaFileIds)) {
            return;
        }
        $mediaFileDeletions = [];

        foreach ($mediaFileIds as $mediaFileId) {
            $mediaFileDeletions[] = [
                'id' => $mediaFileId,
            ];
        }
        $this->mediaFileRepository->delete($mediaFileDeletions, $context);
    }
}
