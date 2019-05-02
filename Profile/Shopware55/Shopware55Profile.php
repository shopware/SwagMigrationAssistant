<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationNext\Migration\Converter\ConverterInterface;
use SwagMigrationNext\Migration\Converter\ConverterRegistryInterface;
use SwagMigrationNext\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\GatewayInterface;
use SwagMigrationNext\Migration\Logging\LoggingServiceInterface;
use SwagMigrationNext\Migration\Media\MediaFileServiceInterface;
use SwagMigrationNext\Migration\MigrationContextInterface;
use SwagMigrationNext\Migration\Profile\ProfileInterface;

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
