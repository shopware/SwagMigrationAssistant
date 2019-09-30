<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\DataSetNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MediaFileProcessorService implements MediaFileProcessorServiceInterface
{
    public const MESSAGE_SIZE = 5;

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var DataSetRegistry
     */
    private $dataSetRegistry;

    /**
     * @var LoggingService
     */
    private $loggingService;

    public function __construct(
        EntityRepositoryInterface $mediaFileRepo,
        MessageBusInterface $messageBus,
        DataSetRegistry $dataSetRegistry,
        LoggingService $loggingService
    ) {
        $this->mediaFileRepo = $mediaFileRepo;
        $this->messageBus = $messageBus;
        $this->dataSetRegistry = $dataSetRegistry;
        $this->loggingService = $loggingService;
    }

    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context, int $fileChunkByteSize): void
    {
        $mediaFiles = $this->getMediaFiles($context, $migrationContext);

        $currentDataSet = null;
        $currentCount = 0;
        $messageMediaUuids = [];
        /* @var SwagMigrationMediaFileEntity $mediaFile */
        foreach ($mediaFiles->getElements() as $mediaFile) {
            if ($currentDataSet === null) {
                try {
                    $currentDataSet = $this->dataSetRegistry->getDataSet($migrationContext, $mediaFile->getEntity());
                } catch (DataSetNotFoundException $e) {
                    $this->logDataSetNotFoundException($migrationContext, $mediaFile);
                    continue;
                }
            }

            if ($currentDataSet::getEntity() !== $mediaFile->getEntity()) {
                $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $currentDataSet, $messageMediaUuids);

                try {
                    $messageMediaUuids = [];
                    $currentCount = 0;
                    $currentDataSet = $this->dataSetRegistry->getDataSet($migrationContext, $mediaFile->getEntity());
                } catch (DataSetNotFoundException $e) {
                    $this->logDataSetNotFoundException($migrationContext, $mediaFile);
                    continue;
                }
            }

            ++$currentCount;
            $messageMediaUuids[] = $mediaFile->getMediaId();

            if ($currentCount < self::MESSAGE_SIZE) {
                continue;
            }

            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $currentDataSet, $messageMediaUuids);
            $messageMediaUuids = [];
            $currentCount = 0;
        }

        if ($currentCount > 0) {
            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $currentDataSet, $messageMediaUuids);
        }

        $this->loggingService->saveLogging($context);
    }

    private function getMediaFiles(Context $context, MigrationContextInterface $migrationContext): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $criteria->addFilter(new EqualsFilter('written', true));
        $criteria->setOffset($migrationContext->getOffset());
        $criteria->setLimit($migrationContext->getLimit());
        $criteria->addSorting(new FieldSorting('entity', FieldSorting::ASCENDING));
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));

        return $this->mediaFileRepo->search($criteria, $context);
    }

    private function addMessageToBus(string $runUuid, Context $context, int $fileChunkByteSize, DataSet $dataSet, array $mediaUuids): void
    {
        $message = new ProcessMediaMessage();
        $message->setMediaFileIds($mediaUuids);
        $message->setRunId($runUuid);
        $message->setDataSet($dataSet);
        $message->setFileChunkByteSize($fileChunkByteSize);
        $message->withContext($context);
        $this->messageBus->dispatch($message);
    }

    private function logDataSetNotFoundException(
        MigrationContextInterface $migrationContext,
        SwagMigrationMediaFileEntity $mediaFile
    ): void {
        $this->loggingService->addLogEntry(
            new DataSetNotFoundLog(
                $migrationContext->getRunUuid(),
                $mediaFile->getEntity(),
                $mediaFile->getId(),
                $migrationContext->getConnection()->getProfileName()
            )
        );
    }
}
