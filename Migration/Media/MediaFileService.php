<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class MediaFileService implements MediaFileServiceInterface
{
    /**
     * @var array
     */
    protected $writeArray = [];

    /**
     * @var array
     */
    protected $uuids = [];

    /**
     * @var EntityRepositoryInterface
     */
    protected $mediaFileRepo;

    /**
     * @var EntityWriterInterface
     */
    protected $entityWriter;

    /**
     * @var EntityDefinition
     */
    protected $mediaFileDefinition;

    /**
     * @var ConverterRegistryInterface
     */
    protected $converterRegistry;

    public function __construct(
        EntityRepositoryInterface $mediaFileRepo,
        EntityWriterInterface $entityWriter,
        EntityDefinition $mediaFileDefinition,
        ConverterRegistryInterface $converterRegistry
    ) {
        $this->mediaFileRepo = $mediaFileRepo;
        $this->entityWriter = $entityWriter;
        $this->mediaFileDefinition = $mediaFileDefinition;
        $this->converterRegistry = $converterRegistry;
    }

    public function writeMediaFile(Context $context): void
    {
        $this->checkMediaIdsForDuplicates($context);

        if (empty($this->writeArray)) {
            return;
        }

        $this->entityWriter->insert(
            $this->mediaFileDefinition,
            $this->writeArray,
            WriteContext::createFromContext($context)
        );

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

    public function setWrittenFlag(array $converted, MigrationContextInterface $migrationContext, Context $context): void
    {
        $converter = $this->converterRegistry->getConverter($migrationContext);
        $mediaUuids = $converter->getMediaUuids($converted);

        if (empty($mediaUuids)) {
            return;
        }

        $this->saveWrittenFlag($mediaUuids, $migrationContext, $context);
    }

    /**
     * @psalm-suppress TypeDoesNotContainType
     */
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
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_OR,
            [
                new MultiFilter(
                    MultiFilter::CONNECTION_AND,
                    [
                        new EqualsAnyFilter('mediaId', $mediaIds),
                        new EqualsFilter('written', true),
                        new EqualsFilter('processed', true),
                    ]
                ),

                new MultiFilter(
                    MultiFilter::CONNECTION_AND,
                    [
                        new EqualsAnyFilter('mediaId', $mediaIds),
                        new EqualsFilter('runId', $runId),
                    ]
                ),
            ]
        ));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        /** @var SwagMigrationMediaFileEntity $mediaFile */
        foreach ($mediaFiles->getElements() as $mediaFile) {
            unset($files[$mediaFile->getMediaId()]);
        }

        $this->writeArray = \array_values($files);
    }

    private function saveWrittenFlag(array $mediaUuids, MigrationContextInterface $migrationContext, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaUuids));
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        $updateWrittenMediaFiles = [];
        foreach ($mediaFiles->getElements() as $data) {
            /* @var SwagMigrationMediaFileEntity $data */
            $value = $data->getId();
            $updateWrittenMediaFiles[] = [
                'id' => $value,
                'written' => true,
            ];
        }

        if (empty($updateWrittenMediaFiles)) {
            return;
        }

        $this->entityWriter->update(
            $this->mediaFileDefinition,
            $updateWrittenMediaFiles,
            WriteContext::createFromContext($context)
        );
    }
}
