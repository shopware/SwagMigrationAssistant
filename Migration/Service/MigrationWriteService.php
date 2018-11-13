<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationNext\Migration\Asset\MediaFileServiceInterface;
use SwagMigrationNext\Migration\Data\SwagMigrationDataStruct;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Writer\WriterRegistryInterface;

class MigrationWriteService implements MigrationWriteServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var WriterRegistryInterface[]
     */
    private $writerRegistry;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    public function __construct(
        RepositoryInterface $migrationDataRepo,
        WriterRegistryInterface $writerRegistry,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->writerRegistry = $writerRegistry;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
    }

    public function writeData(MigrationContext $migrationContext, Context $context): void
    {
        $entity = $migrationContext->getEntity();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', $entity));
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $criteria->addFilter(new NotFilter(MultiFilter::CONNECTION_AND, [new EqualsFilter('converted', null)]));
        $criteria->setOffset($migrationContext->getOffset());
        $criteria->setLimit($migrationContext->getLimit());
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $migrationData = $this->migrationDataRepo->search($criteria, $context);

        if ($migrationData->getTotal() === 0) {
            return;
        }

        $converted = [];
        $updateWrittenData = [];
        foreach ($migrationData->getElements() as $data) {
            /* @var SwagMigrationDataStruct $data */
            $value = $data->getConverted();
            if ($value !== null) {
                $converted[] = $value;
                $updateWrittenData[] = [
                    'id' => $data->getId(),
                    'written' => true,
                ];
            }
        }

        if (empty($converted)) {
            return;
        }

        try {
            $currentWriter = $this->writerRegistry->getWriter($entity);
            $currentWriter->writeData($converted, $context);
        } catch (\Exception $exception) {
            $this->loggingService->addError($migrationContext->getRunUuid(), (string) $exception->getCode(), '', $exception->getMessage(), ['entity' => $entity]);
            $this->loggingService->saveLogging($context);

            return;
        } finally {
            // Update written-Flag of the entity in the data table
            $this->migrationDataRepo->update($updateWrittenData, $context);
        }

        // Update written-Flag of the media file in the media file table
        if ($entity === MediaDefinition::getEntityName() || $entity === ProductDefinition::getEntityName()) {
            $this->mediaFileService->setWrittenFlag($converted, $migrationContext, $context);
        }
    }
}
