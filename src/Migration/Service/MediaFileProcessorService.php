<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use SwagMigrationAssistant\Exception\DataSetNotFoundException;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSetRegistry;
use SwagMigrationAssistant\Migration\Logging\Log\DataSetNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\LoggingService;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('services-settings')]
class MediaFileProcessorService implements MediaFileProcessorServiceInterface
{
    final public const MESSAGE_SIZE = 5;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly DataSetRegistry $dataSetRegistry,
        private readonly LoggingService $loggingService,
        private readonly Connection $dbalConnection
    ) {
    }

    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context): int
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
                $this->addMessageToBus($migrationContext->getRunUuid(), $context, $currentDataSet, $messageMediaUuids);

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
            $messageMediaUuids[] = $mediaFile['media_id'];

            if ($currentCount < self::MESSAGE_SIZE) {
                continue;
            }

            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $currentDataSet, $messageMediaUuids);
            $messageMediaUuids = [];
            $currentCount = 0;
        }

        if ($currentCount > 0 && $currentDataSet !== null) {
            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $currentDataSet, $messageMediaUuids);
        }

        $this->loggingService->saveLogging($context);

        return \count($mediaFiles);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMediaFiles(MigrationContextInterface $migrationContext): array
    {
        $queryBuilder = $this->dbalConnection->createQueryBuilder();

        $result = $queryBuilder
            ->select('*')
            ->from('swag_migration_media_file')
            ->where('run_id = :runId')
            ->andWhere('written = 1')
            ->orderBy('entity, file_size')
            ->setFirstResult($migrationContext->getOffset())
            ->setMaxResults($migrationContext->getLimit())
            ->setParameter('runId', Uuid::fromHexToBytes($migrationContext->getRunUuid()))
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($result as &$media) {
            $media['id'] = Uuid::fromBytesToHex($media['id']);
            $media['run_id'] = Uuid::fromBytesToHex($media['run_id']);
            $media['media_id'] = Uuid::fromBytesToHex($media['media_id']);
        }

        return $result;
    }

    /**
     * @param array<int, string> $mediaUuids
     */
    private function addMessageToBus(string $runUuid, Context $context, DataSet $dataSet, array $mediaUuids): void
    {
        $message = new ProcessMediaMessage(
            $mediaUuids,
            $runUuid,
            $dataSet::getEntity(),
            $context
        );

        $this->messageBus->dispatch($message);
    }

    /**
     * @param array<string, mixed> $mediaFile
     */
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
