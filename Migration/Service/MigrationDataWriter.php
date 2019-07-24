<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Exception\WriterNotFoundException;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Writer\WriterRegistryInterface;

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

    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var EntityDefinition
     */
    private $dataDefinition;

    public function __construct(
        EntityWriterInterface $entityWriter,
        EntityRepositoryInterface $migrationDataRepo,
        WriterRegistryInterface $writerRegistry,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService,
        EntityDefinition $dataDefinition
    ) {
        $this->migrationDataRepo = $migrationDataRepo;
        $this->writerRegistry = $writerRegistry;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
        $this->entityWriter = $entityWriter;
        $this->dataDefinition = $dataDefinition;
    }

    public function writeData(MigrationContextInterface $migrationContext, Context $context): void
    {
        $dataSet = $migrationContext->getDataSet();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', $dataSet::getEntity()));
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $criteria->addFilter(new EqualsFilter('convertFailure', false));
        $criteria->setOffset($migrationContext->getOffset());
        $criteria->setLimit($migrationContext->getLimit());
        $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING));
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
                $converted[$data->getId()] = $value;
                $updateWrittenData[$data->getId()] = [
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
            $currentWriter->writeData(array_values($converted), $context);
        } catch (WriterNotFoundException $writerNotFoundException) {
            $code = $writerNotFoundException->getCode();
            if (is_subclass_of($writerNotFoundException, ShopwareHttpException::class, false)) {
                $code = $writerNotFoundException->getErrorCode();
            }

            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $migrationContext->getRunUuid(),
                $dataSet::getEntity(),
                $writerNotFoundException
            ));
            $this->loggingService->saveLogging($context);

            return;
        } catch (\Exception $exception) {
            $this->writePerEntity($converted, $dataSet::getEntity(), $updateWrittenData, $migrationContext, $context);
        } finally {
            // Update written-Flag of the entity in the data table
            $this->entityWriter->update(
                $this->dataDefinition,
                array_values($updateWrittenData),
                WriteContext::createFromContext($context)
            );
        }

        // Update written-Flag of the media file in the media file table
        if (
            $dataSet::getEntity() === DefaultEntities::MEDIA
            || $dataSet::getEntity() === DefaultEntities::PRODUCT
            || $dataSet::getEntity() === DefaultEntities::PROPERTY_GROUP_OPTION
            || $dataSet::getEntity() === DefaultEntities::CATEGORY
            || $dataSet::getEntity() === DefaultEntities::ORDER_DOCUMENT
        ) {
            $this->mediaFileService->setWrittenFlag($converted, $migrationContext, $context);
        }
    }

    private function writePerEntity(
        array $converted,
        string $entityName,
        array &$updateWrittenData,
        MigrationContextInterface $migrationContext,
        Context $context
    ) {
        foreach ($converted as $dataId => $entity) {
            try {
                $currentWriter = $this->writerRegistry->getWriter($entityName);
                $currentWriter->writeData([$entity], $context);
            } catch (\Exception $exception) {
                $code = $exception->getCode();
                if (is_subclass_of($exception, ShopwareHttpException::class, false)) {
                    $code = $exception->getErrorCode();
                }

                $this->loggingService->addLogEntry(new ExceptionRunLog(
                    $migrationContext->getRunUuid(),
                    $entityName,
                    $exception
                ));
                $this->loggingService->saveLogging($context);

                $updateWrittenData[$dataId]['written'] = false;
                $updateWrittenData[$dataId]['writeFailure'] = true;
            }
        }
    }
}
