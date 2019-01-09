<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\Asset\MediaFileProcessorRegistryInterface;
use SwagMigrationNext\Migration\Asset\SwagMigrationMediaFileStruct;
use SwagMigrationNext\Migration\MigrationContext;

class MediaFileProcessorService implements MediaFileProcessorServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var MediaFileProcessorRegistryInterface
     */
    private $mediaFileProcessorRegistry;

    public function __construct(
        RepositoryInterface $mediaFileRepo,
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
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));
        $migrationData = $this->mediaFileRepo->search($criteria, $context);

        $mediaUuids = [];
        foreach ($migrationData->getElements() as $mediaFile) {
            /* @var SwagMigrationMediaFileStruct $mediaFile */
            $mediaUuids[] = $mediaFile->getMediaId();
        }

        return $mediaUuids;
    }

    public function processMediaFiles(MigrationContext $migrationContext, Context $context, array $workload, int $fileChunkByteSize): array
    {
        $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);

        return $processor->process($migrationContext, $context, $workload, $fileChunkByteSize);
    }
}
