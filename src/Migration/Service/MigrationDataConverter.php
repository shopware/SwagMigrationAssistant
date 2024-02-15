<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\Logging\Log\ExceptionRunLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingDeltaResult;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class MigrationDataConverter implements MigrationDataConverterInterface
{
    public function __construct(
        private readonly EntityWriterInterface $entityWriter,
        private readonly ConverterRegistryInterface $converterRegistry,
        private readonly MediaFileServiceInterface $mediaFileService,
        private readonly LoggingServiceInterface $loggingService,
        private readonly EntityDefinition $dataDefinition,
        private readonly MappingServiceInterface $mappingService
    ) {
    }

    public function convert(array $data, MigrationContextInterface $migrationContext, Context $context): void
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return;
        }

        try {
            $converter = $this->converterRegistry->getConverter($migrationContext);
            $result = $this->filterDeltas($data, $converter, $migrationContext, $context);
            $data = $result->getMigrationData();
            $preloadIds = $result->getPreloadIds();

            if (\count($data) > 0) {
                if (!empty($preloadIds)) {
                    $this->mappingService->preloadMappings($preloadIds, $context);
                }
                $createData = $this->convertData($context, $data, $converter, $migrationContext, $dataSet);

                if (\count($createData) === 0) {
                    return;
                }
                $this->entityWriter->upsert(
                    $this->dataDefinition,
                    $createData,
                    WriteContext::createFromContext($context)
                );
                $converter->writeMapping($context);
                $this->loggingService->saveLogging($context);
                $this->mediaFileService->writeMediaFile($context);
            }
        } catch (\Throwable $exception) {
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
            } catch (\Throwable $exception) {
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
            $checksums[$converter->getSourceIdentifier($dataSet)] = \md5(\serialize($dataSet));
        }

        $connection = $migrationContext->getConnection();
        $dataSet = $migrationContext->getDataSet();

        if ($connection === null || $dataSet === null) {
            return new MappingDeltaResult();
        }

        $connectionId = $connection->getId();
        $entity = $dataSet::getEntity();
        $result = $this->mappingService->getMappings($connectionId, $entity, \array_keys($checksums), $context);

        if ($result->getTotal() > 0) {
            $relatedMappings = [];
            foreach ($result->getEntities() as $mapping) {
                $oldIdentifier = $mapping->getOldIdentifier();
                if ($oldIdentifier === null) {
                    continue;
                }

                $checksum = $mapping->getChecksum();
                $preloadIds[] = $mapping->getId();
                if (isset($checksums[$oldIdentifier]) && $checksums[$oldIdentifier] === $checksum) {
                    unset($mappedData[$oldIdentifier]);
                }

                $additionalData = $mapping->getAdditionalData();
                if (isset($additionalData['relatedMappings'])) {
                    $relatedMappings[] = $additionalData['relatedMappings'];
                }
            }

            if ($relatedMappings !== []) {
                $preloadIds = \array_values(
                    \array_unique(\array_merge($preloadIds, ...$relatedMappings))
                );
            }
        }
        $resultSet = new MappingDeltaResult(\array_values($mappedData), $preloadIds);
        unset($checksums, $mappedData);

        return $resultSet;
    }
}
