<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Exception\ProcessorNotFoundException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\Log\ProcessorNotFoundLog;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorInterface;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\MediaProcessWorkloadStruct;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContextFactoryInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

class ProcessMediaHandler extends AbstractMessageHandler
{
    public const MEDIA_ERROR_THRESHOLD = 3;

    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var MediaFileProcessorRegistryInterface
     */
    private $mediaFileProcessorRegistry;

    /**
     * @var LoggingServiceInterface
     */
    private $loggingService;

    /**
     * @var MigrationContextFactoryInterface
     */
    private $migrationContextFactory;

    public function __construct(
        EntityRepositoryInterface $migrationRunRepo,
        MediaFileProcessorRegistryInterface $mediaFileProcessorRegistry,
        LoggingServiceInterface $loggingService,
        MigrationContextFactoryInterface $migrationContextFactory
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
        $this->mediaFileProcessorRegistry = $mediaFileProcessorRegistry;
        $this->loggingService = $loggingService;
        $this->migrationContextFactory = $migrationContextFactory;
    }

    /**
     * @param ProcessMediaMessage $message
     *
     * @throws EntityNotExistsException
     */
    public function handle($message): void
    {
        $context = $message->readContext();

        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$message->getRunId()]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $message->getRunId());
        }

        $connection = $run->getConnection();
        if ($connection === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $message->getRunId());
        }

        $migrationContext = $this->migrationContextFactory->create($run, 0, 0, $message->getDataSet()::getEntity());

        if ($migrationContext === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $message->getRunId());
        }

        $workload = [];
        foreach ($message->getMediaFileIds() as $mediaFileId) {
            $workload[] = new MediaProcessWorkloadStruct(
                $mediaFileId,
                $message->getRunId(),
                MediaProcessWorkloadStruct::IN_PROGRESS_STATE
            );
        }

        try {
            $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);
            $workload = $processor->process($migrationContext, $context, $workload, $message->getFileChunkByteSize());
            $this->processFailures($context, $migrationContext, $processor, $workload, $message->getFileChunkByteSize());
        } catch (ProcessorNotFoundException $e) {
            $this->loggingService->addLogEntry(new ProcessorNotFoundLog(
                $message->getRunId(),
                $message->getDataSet()::getEntity(),
                $connection->getProfileName(),
                $connection->getGatewayName()
            ));

            $this->loggingService->saveLogging($context);
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ProcessMediaMessage::class,
        ];
    }

    /**
     * @param MediaProcessWorkloadStruct[] $workload
     */
    private function processFailures(
        Context $context,
        MigrationContextInterface $migrationContext,
        MediaFileProcessorInterface $processor,
        array $workload,
        int $fileChunkByteSize
    ): void {
        for ($i = 0; $i < self::MEDIA_ERROR_THRESHOLD; ++$i) {
            $errorWorkload = [];

            foreach ($workload as $item) {
                if ($item->getErrorCount() > 0) {
                    $errorWorkload[] = $item;
                }
            }

            if (empty($errorWorkload)) {
                break;
            }

            $workload = $processor->process($migrationContext, $context, $errorWorkload, $fileChunkByteSize);
        }
    }
}
