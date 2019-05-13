<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Profile\Shopware55;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Migration\Converter\ConverterInterface;
use SwagMigrationAssistant\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\EnvironmentInformation;
use SwagMigrationAssistant\Migration\Gateway\GatewayInterface;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Profile\ProfileInterface;

class Shopware55Profile implements ProfileInterface
{
    public const PROFILE_NAME = 'shopware55';

    public const SOURCE_SYSTEM_NAME = 'Shopware';

    public const SOURCE_SYSTEM_VERSION = '5.5';

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

    public function __construct(
        EntityWriterInterface $entityWriter,
        ConverterRegistryInterface $converterRegistry,
        MediaFileServiceInterface $mediaFileService,
        LoggingServiceInterface $loggingService,
        EntityDefinition $dataDefinition
    ) {
        $this->entityWriter = $entityWriter;
        $this->converterRegistry = $converterRegistry;
        $this->mediaFileService = $mediaFileService;
        $this->loggingService = $loggingService;
        $this->dataDefinition = $dataDefinition;
    }

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function convert(array $data, MigrationContextInterface $migrationContext, Context $context): int
    {
        $converter = $this->converterRegistry->getConverter($migrationContext);
        $createData = $this->convertData($context, $data, $converter, $migrationContext, $migrationContext->getDataSet());

        if (\count($createData) === 0) {
            return 0;
        }

        $converter->writeMapping($context);
        $this->mediaFileService->writeMediaFile($context);
        $this->loggingService->saveLogging($context);

        /** @var EntityWriteResult[] $writtenEvents */
        $writtenEvents = $this->entityWriter->upsert(
            $this->dataDefinition,
            $createData,
            WriteContext::createFromContext($context)
        );

        return \count($writtenEvents);
    }

    public function readEnvironmentInformation(GatewayInterface $gateway): EnvironmentInformation
    {
        return $gateway->readEnvironmentInformation();
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
                    'convertFailure' => $convertFailureFlag,
                ];
            } catch (\Exception $exception) {
                $errorCode = $exception->getCode();
                if (is_subclass_of($exception, ShopwareHttpException::class)) {
                    $errorCode = $exception->getErrorCode();
                }

                $this->loggingService->addError(
                    $runUuid,
                    (string) $errorCode,
                    '',
                    $exception->getMessage(),
                    [
                        'entity' => $dataSet::getEntity(),
                        'raw' => $item,
                    ]
                );

                $createData[] = [
                    'entity' => $dataSet::getEntity(),
                    'runId' => $runUuid,
                    'raw' => $item,
                    'converted' => null,
                    'unmapped' => $item,
                    'convertFailure' => true,
                ];
                continue;
            }
        }

        return $createData;
    }
}
