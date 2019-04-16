<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Service;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationNext\Migration\Data\SwagMigrationDataEntity;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Writer\WriterRegistryInterface;

class MigrationDataWriter implements MigrationDataWriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $migrationDataRepo;

    /**
     * @var WriterRegistryInterface
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
        EntityRepositoryInterface $migrationDataRepo,
        WriterRegistryInterface $writerRegistry,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService
    ) {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->writerRegistry = $writerRegistry;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
    }

    public function writeData(MigrationContextInterface $migrationContext, Context $context): void
    {
        $dataSet = $migrationContext->getDataSet();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', $dataSet::getEntity()));
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
            /* @var SwagMigrationDataEntity $data */
            $value = $data->getConverted();
            if ($value !== null) {
                $converted[] = $value;
                $updateWrittenData[] = [
                    'id' => $data->getId(),
                    'written' => true,
                    'writeFailure' => false,
                ];
            }
        }

        if (empty($converted)) {
            return;
        }

        try {
            $currentWriter = $this->writerRegistry->getWriter($dataSet::getEntity());
            $currentWriter->writeData($converted, $context);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            if (is_subclass_of($exception, ShopwareHttpException::class, false)) {
                $code = $exception->getErrorCode();
            }

            $this->loggingService->addError($migrationContext->getRunUuid(), (string) $code, '', $exception->getMessage(), ['entity' => $dataSet::getEntity()]);
            $this->loggingService->saveLogging($context);

            foreach ($updateWrittenData as &$writtenData) {
                $writtenData['written'] = false;
                $writtenData['writeFailure'] = true;
            }

            return;
        } finally {
            // Update written-Flag of the entity in the data table
            $this->migrationDataRepo->update($updateWrittenData, $context);
        }

        // Update written-Flag of the media file in the media file table
        if (
            $dataSet::getEntity() === MediaDefinition::getEntityName()
            || $dataSet::getEntity() === ProductDefinition::getEntityName()
            || $dataSet::getEntity() === PropertyGroupOptionDefinition::getEntityName()
        ) {
            $this->mediaFileService->setWrittenFlag($converted, $migrationContext, $context);
        }
    }
}
