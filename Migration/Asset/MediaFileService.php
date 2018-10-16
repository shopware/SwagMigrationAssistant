<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Asset;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\ORM\RepositoryInterface;
use Shopware\Core\Framework\ORM\Search\Criteria;
use Shopware\Core\Framework\ORM\Search\Query\TermQuery;
use Shopware\Core\Framework\ORM\Search\Query\TermsQuery;
use SwagMigrationNext\Migration\MigrationContext;

class MediaFileService implements MediaFileServiceInterface
{
    protected $writeArray = [];

    protected $uuids = [];
    /**
     * @var RepositoryInterface
     */
    private $mediaFileRepo;

    public function __construct(RepositoryInterface $mediaFileRepo)
    {
        $this->mediaFileRepo = $mediaFileRepo;
    }

    public function writeMediaFile(Context $context): void
    {
        $this->checkMediaIdsForDuplicates($context);

        if (empty($this->writeArray)) {
            return;
        }

        $this->mediaFileRepo->create($this->writeArray, $context);
        $this->writeArray = [];
        $this->uuids = [];
    }

    public function saveMediaFile(array $mediaFile): void
    {
        $mediaId = $mediaFile['mediaId'];
        if (isset($this->uuids[$mediaId])) {
            return;
        }

        $this->uuids[$mediaId] = $mediaId;
        $this->writeArray[] = $mediaFile;
    }

    public function setWrittenFlag(array $converted, MigrationContext $migrationContext, Context $context): void
    {
        $entity = $migrationContext->getEntity();
        $mediaUuids = [];
        foreach ($converted as $data) {
            if ($entity === MediaDefinition::getEntityName()) {
                $mediaUuids[] = $data['id'];
                continue;
            }

            if ($entity === ProductDefinition::getEntityName()) {
                if (!isset($data['media'])) {
                    continue;
                }

                foreach ($data['media'] as $media) {
                    if (!isset($media['media'])) {
                        continue;
                    }

                    $mediaUuids[] = $media['media']['id'];
                }
            }
        }

        if (empty($mediaUuids)) {
            return;
        }

        $this->saveWrittenFlag($mediaUuids, $migrationContext, $context);
    }

    private function checkMediaIdsForDuplicates(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        $runId = null;
        $files = [];
        $mediaIds = [];
        foreach ($this->writeArray as $mediaFile) {
            if ($runId === null) {
                $runId = $mediaFile['runId'];
            }

            $files[$mediaFile['mediaId']] = $mediaFile;
            $mediaIds[] = $mediaFile['mediaId'];
        }

        if (empty($mediaIds)) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runId));
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaIds));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        /** @var $mediaFile SwagMigrationMediaFileStruct */
        foreach ($mediaFiles->getElements() as $mediaFile) {
            unset($files[$mediaFile->getMediaId()]);
        }
        $this->writeArray = array_values($files);
    }

    private function saveWrittenFlag(array $mediaUuids, MigrationContext $migrationContext, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaUuids));
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        $updateWrittenMediaFiles = [];
        foreach ($mediaFiles->getElements() as $data) {
            /* @var SwagMigrationMediaFileStruct $data */
            $value = $data->getId();
            $updateWrittenMediaFiles[] = [
                'id' => $value,
                'written' => true,
            ];
        }

        if (empty($updateWrittenMediaFiles)) {
            return;
        }

        $this->mediaFileRepo->update($updateWrittenMediaFiles, $context);
    }
}
