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
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use SwagMigrationAssistant\Exception\WriterNotFoundException;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataEntity;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\WriteExceptionRunLog;
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
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $migrationContext->getRunUuid(),
                $dataSet::getEntity(),
                $writerNotFoundException
            ));
            $this->loggingService->saveLogging($context);

            foreach ($updateWrittenData as $id => &$data) {
                $data['written'] = false;
                $data['writeFailure'] = true;
            }
            unset($data);

            return;
        } catch (WriteException $exception) {
            $this->handleWriteException(
                $exception,
                $converted,
                $dataSet::getEntity(),
                $updateWrittenData,
                $migrationContext,
                $context
            );
        } catch (\Exception $exception) {
            // Worst case: something unknown goes wrong (most likely some foreign key constraint that fails)
            // TODO: If the core catches the exceptions and writes the remaining valid data this must be refactored.
            $this->writePerEntity($converted, $dataSet::getEntity(), $updateWrittenData, $migrationContext, $context);
        } finally {
            // Update written-Flag of the entity in the data table
            $this->entityWriter->update(
                $this->dataDefinition,
                array_values($updateWrittenData),
                WriteContext::createFromContext($context)
            );
            $this->loggingService->saveLogging($context);
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

    private function handleWriteException(
        WriteException $exception,
        array $converted,
        string $entityName,
        array &$updateWrittenData,
        MigrationContextInterface $migrationContext,
        Context $context
    ): void {
        $writeErrors = $this->extractWriteErrorsWithIndex($exception);
        $currentWriter = $this->writerRegistry->getWriter($entityName);
        $newData = [];

        $index = 0;
        foreach ($converted as $dataId => $entity) {
            if (!isset($writeErrors[$index])) {
                $newData[] = $entity;
                ++$index;
                continue;
            }

            $updateWrittenData[$dataId]['written'] = false;
            $updateWrittenData[$dataId]['writeFailure'] = true;
            $this->loggingService->addLogEntry(new WriteExceptionRunLog(
                $migrationContext->getRunUuid(),
                $entityName,
                $writeErrors[$index],
                $dataId
            ));

            ++$index;
        }

        $currentWriter->writeData($newData, $context);
    }

    private function extractWriteErrorsWithIndex(WriteException $exception): array
    {
        $writeErrors = [];
        foreach ($exception->getErrors() as $error) {
            $pointer = $error['source']['pointer'] ?? '/';
            preg_match('/^\/(\d+)\//', $pointer, $matches, PREG_UNMATCHED_AS_NULL);

            if (isset($matches[1])) {
                $index = (int) $matches[1];
                $writeErrors[$index] = $error;
            }
        }

        return $writeErrors;
    }

    private function writePerEntity(
        array $converted,
        string $entityName,
        array &$updateWrittenData,
        MigrationContextInterface $migrationContext,
        Context $context
    ): void {
        foreach ($converted as $dataId => $entity) {
            try {
                $currentWriter = $this->writerRegistry->getWriter($entityName);
                $currentWriter->writeData([$entity], $context);
            } catch (\Exception $exception) {
                $this->loggingService->addLogEntry(new ExceptionRunLog(
                    $migrationContext->getRunUuid(),
                    $entityName,
                    $exception,
                    $dataId
                ));

                $updateWrittenData[$dataId]['written'] = false;
                $updateWrittenData[$dataId]['writeFailure'] = true;
            }
        }
    }
}
