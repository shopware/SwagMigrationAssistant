<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Media;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Contracts\Service\ResetInterface;

#[Package('services-settings')]
class MediaFileService implements MediaFileServiceInterface, ResetInterface
{
    protected array $writeArray = [];

    protected array $uuids = [];

    /**
     * @param EntityRepository<SwagMigrationMediaFileCollection> $mediaFileRepo
     */
    public function __construct(
        protected EntityRepository $mediaFileRepo,
        protected EntityWriterInterface $entityWriter,
        protected EntityDefinition $mediaFileDefinition,
        protected ConverterRegistryInterface $converterRegistry,
        protected LoggerInterface $logger
    ) {
    }

    public function reset(): void
    {
        if (!empty($this->writeArray)) {
            $this->logger->error('SwagMigrationAssistant: Migration media file service was not empty on calling reset.');
        }

        $this->writeArray = [];
        $this->uuids = [];
    }

    public function writeMediaFile(Context $context): void
    {
        $this->checkMediaIdsForDuplicates($context);

        if (empty($this->writeArray)) {
            return;
        }

        try {
            $this->entityWriter->insert(
                $this->mediaFileDefinition,
                $this->writeArray,
                WriteContext::createFromContext($context)
            );
        } catch (\Exception) {
            $this->writePerEntry($context);
        } finally {
            $this->writeArray = [];
            $this->uuids = [];
        }
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

    public function filterUnwrittenData(array $converted, array $updateWrittenData)
    {
        foreach ($converted as $dataId => $entity) {
            if (!isset($updateWrittenData[$dataId])) {
                unset($converted[$dataId]);
            }

            if ($updateWrittenData[$dataId]['written'] === true) {
                continue;
            }

            unset($converted[$dataId]);
        }

        return $converted;
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
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context)->getEntities();

        foreach ($mediaFiles as $mediaFile) {
            unset($files[$mediaFile->getMediaId()]);
        }

        $this->writeArray = \array_values($files);
    }

    private function saveWrittenFlag(array $mediaUuids, MigrationContextInterface $migrationContext, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaUuids));
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context)->getEntities();

        $updateWrittenMediaFiles = [];
        foreach ($mediaFiles as $data) {
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

    private function writePerEntry(Context $context): void
    {
        foreach ($this->writeArray as $media) {
            try {
                $this->entityWriter->insert(
                    $this->mediaFileDefinition,
                    [$media],
                    WriteContext::createFromContext($context)
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'SwagMigrationAssistant: Could not write media file.',
                    [
                        'error' => $e->getMessage(),
                        'media' => $media,
                    ]
                );
            }
        }
    }
}
