<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('services-settings')]
class MediaFolderWriter extends AbstractWriter
{
    /**
     * @param EntityRepository<MediaFolderCollection> $mediaFolderRepo
     */
    public function __construct(
        EntityWriterInterface $entityWriter,
        EntityDefinition $definition,
        private readonly EntityRepository $mediaFolderRepo
    ) {
        parent::__construct($entityWriter, $definition);
    }

    public function supports(): string
    {
        return DefaultEntities::MEDIA_FOLDER;
    }

    public function writeData(array $data, Context $context): array
    {
        $defaultFolderIds = [];

        foreach ($data as $entry) {
            if (isset($entry['defaultFolderId'])) {
                $defaultFolderIds[] = $entry['defaultFolderId'];
            }

            if (isset($entry['defaultFolder']) && isset($entry['defaultFolder']['id'])) {
                $defaultFolderIds[] = $entry['defaultFolder']['id'];
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('defaultFolderId', $defaultFolderIds));
        $ids = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($criteria) {
            return $this->mediaFolderRepo->searchIds($criteria, $context)->getIds();
        });

        $update = [];

        foreach ($ids as $id) {
            $update[] = [
                'id' => $id,
                'defaultFolderId' => null,
            ];
        }

        if (\count($update) > 0) {
            $this->mediaFolderRepo->update($update, $context);
        }

        return parent::writeData($data, $context);
    }
}
