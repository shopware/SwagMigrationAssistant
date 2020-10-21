<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\DataSetNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MediaFileProcessorService implements MediaFileProcessorServiceInterface
{
    public const MESSAGE_SIZE = 5;

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

    /**
     * @var Connection
     */
    private $dbalConnection;

    public function __construct(
        MessageBusInterface $messageBus,
        DataSetRegistry $dataSetRegistry,
        LoggingService $loggingService,
        Connection $dbalConnection
    ) {
        $this->messageBus = $messageBus;
        $this->dataSetRegistry = $dataSetRegistry;
        $this->loggingService = $loggingService;
        $this->dbalConnection = $dbalConnection;
    }

    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context, int $fileChunkByteSize): void
    {
        $mediaFiles = $this->getMediaFiles($migrationContext);

        $currentDataSet = null;
        $currentCount = 0;
        $messageMediaUuids = [];
        foreach ($mediaFiles as $mediaFile) {
            if ($currentDataSet === null) {
                try {
                    $currentDataSet = $this->dataSetRegistry->getDataSet($migrationContext, $mediaFile['entity']);
                } catch (DataSetNotFoundException $e) {
                    $this->logDataSetNotFoundException($migrationContext, $mediaFile);

                    continue;
                }
            }

            if ($currentDataSet::getEntity() !== $mediaFile['entity']) {
                /*
                 * @psalm-suppress PossiblyNullArgument
                 */
                $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $currentDataSet, $messageMediaUuids);

                try {
                    $messageMediaUuids = [];
                    $currentCount = 0;
                    $currentDataSet = $this->dataSetRegistry->getDataSet($migrationContext, $mediaFile['entity']);
                } catch (DataSetNotFoundException $e) {
                    $this->logDataSetNotFoundException($migrationContext, $mediaFile);

                    continue;
                }
            }

            ++$currentCount;
            $messageMediaUuids[] = Uuid::fromBytesToHex($mediaFile['media_id']);

            if ($currentCount < self::MESSAGE_SIZE) {
                continue;
            }

            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $currentDataSet, $messageMediaUuids);
            $messageMediaUuids = [];
            $currentCount = 0;
        }

        if ($currentCount > 0 && $currentDataSet !== null) {
            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $currentDataSet, $messageMediaUuids);
        }

        $this->loggingService->saveLogging($context);
    }

    private function getMediaFiles(MigrationContextInterface $migrationContext): array
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder();
        $query = $queryBuilder
            ->select('*')
            ->from('swag_migration_media_file')
            ->where('HEX(run_id) = :runId')
            ->andWhere('written = 1')
            ->orderBy('entity, file_size')
            ->setFirstResult($migrationContext->getOffset())
            ->setMaxResults($migrationContext->getLimit())
            ->setParameter('runId', $migrationContext->getRunUuid());

        $query = $query->execute();
        if (!($query instanceof ResultStatement)) {
            return [];
        }

        return $query->fetchAll(FetchMode::ASSOCIATIVE);
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
        array $mediaFile
    ): void {
        $connection = $migrationContext->getConnection();

        if ($connection === null) {
            return;
        }

        $this->loggingService->addLogEntry(
            new DataSetNotFoundLog(
                $migrationContext->getRunUuid(),
                $mediaFile['entity'],
                $mediaFile['id'],
                $connection->getProfileName()
            )
        );
    }
}
