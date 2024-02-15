<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Exception\WriterNotFoundException;
use SwagMigrationAssistant\Migration\Data\SwagMigrationDataCollection;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\Log\WriteExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingCollection;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Writer\WriterRegistryInterface;

#[Package('services-settings')]
class MigrationDataWriter implements MigrationDataWriterInterface
{
    /**
     * @param EntityRepository<SwagMigrationDataCollection> $migrationDataRepo
     * @param EntityRepository<SwagMigrationMappingCollection> $mappingRepo
     */
    public function __construct(
        private readonly EntityWriterInterface $entityWriter,
        private readonly EntityRepository $migrationDataRepo,
        private readonly WriterRegistryInterface $writerRegistry,
        private readonly MediaFileServiceInterface $mediaFileService,
        private readonly LoggingServiceInterface $loggingService,
        private readonly EntityDefinition $dataDefinition,
        private readonly EntityRepository $mappingRepo
    ) {
    }

    public function writeData(MigrationContextInterface $migrationContext, Context $context): int
    {
        $dataSet = $migrationContext->getDataSet();

        if ($dataSet === null) {
            return 0;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', $dataSet::getEntity()));
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $criteria->addFilter(new EqualsFilter('convertFailure', false));
        $criteria->setOffset($migrationContext->getOffset());
        $criteria->setLimit($migrationContext->getLimit());
        $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING));
        $migrationData = $this->migrationDataRepo->search($criteria, $context);

        if ($migrationData->getTotal() === 0) {
            return 0;
        }

        $converted = [];
        $mappingIds = [];
        $updateWrittenData = [];

        foreach ($migrationData->getEntities() as $data) {
            $value = $data->getConverted();
            if ($value !== null) {
                $converted[$data->getId()] = $value;
                if ($data->getMappingUuid() !== null) {
                    $mappingIds[$data->getId()] = $data->getMappingUuid();
                }
                $updateWrittenData[$data->getId()] = [
                    'id' => $data->getId(),
                    'written' => true,
                    'writeFailure' => false,
                ];
            }
        }

        if (empty($converted)) {
            return 0;
        }

        try {
            $currentWriter = $this->writerRegistry->getWriter($dataSet::getEntity());
            $currentWriter->writeData(\array_values($converted), $context);
        } catch (WriterNotFoundException $writerNotFoundException) {
            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $migrationContext->getRunUuid(),
                $dataSet::getEntity(),
                $writerNotFoundException
            ));
            $this->loggingService->saveLogging($context);

            foreach ($updateWrittenData as &$data) {
                $data['written'] = false;
                $data['writeFailure'] = true;
            }
            unset($data);

            return $migrationData->getTotal();
        } catch (WriteException $exception) {
            $this->handleWriteException(
                $exception,
                $converted,
                $dataSet::getEntity(),
                $updateWrittenData,
                $migrationContext,
                $context
            );
        } catch (\Throwable $exception) {
            // Worst case: something unknown goes wrong (most likely some foreign key constraint that fails)
            $this->writePerEntity($converted, $dataSet::getEntity(), $updateWrittenData, $migrationContext, $context);
        } finally {
            // Update written-Flag of the entity in the data table
            $this->entityWriter->update(
                $this->dataDefinition,
                \array_values($updateWrittenData),
                WriteContext::createFromContext($context)
            );
            $this->removeChecksumsOfUnwrittenData($updateWrittenData, $mappingIds, $context);
            $this->loggingService->saveLogging($context);
        }

        // Update written-Flag of the media file in the media file table
        $this->mediaFileService->setWrittenFlag(
            $this->mediaFileService->filterUnwrittenData($converted, $updateWrittenData),
            $migrationContext,
            $context
        );

        return $migrationData->getTotal();
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

        if (empty($newData)) {
            return;
        }

        try {
            $currentWriter->writeData($newData, $context);
        } catch (\Throwable $exception) {
            $this->writePerEntity($converted, $entityName, $updateWrittenData, $migrationContext, $context);
        }
    }

    private function extractWriteErrorsWithIndex(WriteException $exception): array
    {
        $writeErrors = [];
        foreach ($exception->getErrors() as $error) {
            $pointer = $error['source']['pointer'] ?? '/';
            \preg_match('/^\/(\d+)\//', $pointer, $matches, \PREG_UNMATCHED_AS_NULL);

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
            } catch (\Throwable $exception) {
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

    /**
     * Remove hashes from mapping entry of datasets which could
     * not be written, so that they won´t be skipped in next conversion.
     */
    private function removeChecksumsOfUnwrittenData(
        array $updateWrittenData,
        array $mappingIds,
        Context $context
    ): void {
        $mappingsRequireUpdate = [];
        foreach ($updateWrittenData as $dataId => $data) {
            if ($data['written'] === false) {
                if (isset($mappingIds[$dataId])) {
                    $mappingsRequireUpdate[] = [
                        'id' => $mappingIds[$dataId],
                        'checksum' => null,
                    ];
                }
            }
        }

        if (empty($mappingsRequireUpdate)) {
            return;
        }

        // check if mappings exist
        $existingMappingIds = $this->mappingRepo->searchIds(new Criteria(\array_column($mappingsRequireUpdate, 'id')), $context)->getIds();
        $mappingsRequireUpdate = \array_filter($mappingsRequireUpdate, static function (array $update) use ($existingMappingIds) {
            return \in_array($update['id'], $existingMappingIds, true);
        });

        if (empty($mappingsRequireUpdate)) {
            return;
        }

        $this->mappingRepo->update(\array_values($mappingsRequireUpdate), $context);
    }
}
