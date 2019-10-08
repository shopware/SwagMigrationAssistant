<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingDeltaResult;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\SwagMigrationMappingEntity;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class MigrationDataConverter implements MigrationDataConverterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var ConverterRegistryInterface
     */
    private $converterRegistry;

    /**
     * @var MediaFileServiceInterface
     */
    private $mediaFileService;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var EntityDefinition
     */
    private $dataDefinition;

    /**
     * @var MappingServiceInterface
     */
    private $mappingService;

    public function __construct(
        EntityWriterInterface $entityWriter,
        ConverterRegistryInterface $converterRegistry,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService,
        EntityDefinition $dataDefinition,
        MappingServiceInterface $mappingService
    ) {
        $this->entityWriter = $entityWriter;
        $this->converterRegistry = $converterRegistry;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
        $this->dataDefinition = $dataDefinition;
        $this->mappingService = $mappingService;
    }

    public function convert(array $data, MigrationContextInterface $migrationContext, Context $context): void
    {
        try {
            $converter = $this->converterRegistry->getConverter($migrationContext);
            $result = $this->filterDeltas($data, $converter, $migrationContext, $context);
            $data = $result->getMigrationData();
            $preloadIds = $result->getPreloadIds();

            if (\count($data) > 0) {
                if (!empty($preloadIds)) {
                    $this->mappingService->preloadMappings($preloadIds, $context);
                }
                $createData = $this->convertData($context, $data, $converter, $migrationContext, $migrationContext->getDataSet());

                if (\count($createData) === 0) {
                    return;
                }
                $converter->writeMapping($context);
                $this->mediaFileService->writeMediaFile($context);
                $this->loggingService->saveLogging($context);

                $this->entityWriter->upsert(
                    $this->dataDefinition,
                    $createData,
                    WriteContext::createFromContext($context)
                );
            }
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            if (is_subclass_of($exception, ShopwareHttpException::class, false)) {
                $code = $exception->getErrorCode();
            }

            $dataSet = $migrationContext->getDataSet();

            $this->loggingService->addLogEntry(new ExceptionRunLog(
                $migrationContext->getRunUuid(),
                $dataSet::getEntity(),
                $exception
            ));
            $this->loggingService->saveLogging($context);
        }
    }

    private function convertData(
        Context $context,
        array $data,
        ConverterInterface $converter,
        MigrationContextInterface $migrationContext,
        DataSet $dataSet
    ): array {
        $runUuid = $migrationContext->getRunUuid();

        $createData = [];
        foreach ($data as $item) {
            try {
                $convertStruct = $converter->convert($item, $context, $migrationContext);
                $convertFailureFlag = empty($convertStruct->getConverted());

                $createData[] = [
                    'entity' => $dataSet::getEntity(),
                    'runId' => $runUuid,
                    'raw' => $item,
                    'converted' => $convertStruct->getConverted(),
                    'unmapped' => $convertStruct->getUnmapped(),
                    'mappingUuid' => $convertStruct->getMappingUuid(),
                    'convertFailure' => $convertFailureFlag,
                ];
            } catch (\Exception $exception) {
                $this->loggingService->addLogEntry(new ExceptionRunLog(
                    $runUuid,
                    $dataSet::getEntity(),
                    $exception,
                    $item['id'] ?? null
                ));

                $createData[] = [
                    'entity' => $dataSet::getEntity(),
                    'runId' => $runUuid,
                    'raw' => $item,
                    'converted' => null,
                    'unmapped' => $item,
                    'mappingUuid' => null,
                    'convertFailure' => true,
                ];
                continue;
            }
        }

        return $createData;
    }

    /**
     * Removes all datasets from fetched data which have the same checksum as last time they were migrated.
     * So we ignore identic datasets for repeated migrations.
     */
    private function filterDeltas(array $data, ConverterInterface $converter, MigrationContextInterface $migrationContext, Context $context): MappingDeltaResult
    {
        $mappedData = [];
        $checksums = [];
        $preloadIds = [];

        foreach ($data as $dataSet) {
            $mappedData[$converter->getSourceIdentifier($dataSet)] = $dataSet;
            $checksums[$converter->getSourceIdentifier($dataSet)] = md5(serialize($dataSet));
        }
        $connectionId = $migrationContext->getConnection()->getId();
        $entity = $migrationContext->getDataSet()::getEntity();
        $result = $this->mappingService->getMappings($connectionId, $entity, array_keys($checksums), $context);

        if ($result->getTotal() > 0) {
            $elements = $result->getEntities()->getElements();
            $relatedMappings = [];
            /** @var SwagMigrationMappingEntity $mapping */
            foreach ($elements as $mapping) {
                $checksum = $mapping->getChecksum();
                $preloadIds[] = $mapping->getId();
                if (isset($checksums[$mapping->getOldIdentifier()])
                    && $checksums[$mapping->getOldIdentifier()] === $checksum
                ) {
                    unset($mappedData[$mapping->getOldIdentifier()]);
                }
                $additionalData = $mapping->getAdditionalData();
                if (isset($additionalData['relatedMappings'])) {
                    $relatedMappings[] = $additionalData['relatedMappings'];
                }
            }
            $preloadIds = array_values(
                array_unique(array_merge($preloadIds, ...$relatedMappings))
            );
        }
        $resultSet = new MappingDeltaResult(array_values($mappedData), $preloadIds);
        unset($checksums, $mappedData);

        return $resultSet;
    }
}
