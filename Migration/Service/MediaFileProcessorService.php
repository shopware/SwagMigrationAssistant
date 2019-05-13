<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class MediaFileProcessorService implements MediaFileProcessorServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var MediaFileProcessorRegistryInterface
     */
    private $mediaFileProcessorRegistry;

    public function __construct(
        EntityRepositoryInterface $mediaFileRepo,
        MediaFileProcessorRegistryInterface $mediaFileProcessorRegistry
    ) {
        $this->mediaFileRepo = $mediaFileRepo;
        $this->mediaFileProcessorRegistry = $mediaFileProcessorRegistry;
    }

    public function fetchMediaUuids(string $runUuid, Context $context, int $limit): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $runUuid));
        $criteria->addFilter(new EqualsFilter('written', true));
        $criteria->addFilter(new EqualsFilter('processed', false));
        $criteria->addFilter(new EqualsFilter('processFailure', false));
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));
        $migrationData = $this->mediaFileRepo->search($criteria, $context);

        $mediaUuids = [];
        foreach ($migrationData->getElements() as $mediaFile) {
            /* @var SwagMigrationMediaFileEntity $mediaFile */
            $mediaUuids[] = $mediaFile->getMediaId();
        }

        return $mediaUuids;
    }

    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array
    {
        $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);

        return $processor->process($migrationContext, $context, $workload, $fileChunkByteSize);
    }
}
