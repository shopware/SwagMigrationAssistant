<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\MessageQueue\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use SwagMigrationAssistant\Exception\EntityNotExistsException;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContext;
use SwagMigrationAssistant\Migration\Run\SwagMigrationRunEntity;

class ProcessMediaHandler extends AbstractMessageHandler
{
    /**
     * @var EntityRepositoryInterface
     */
    private $migrationRunRepo;

    /**
     * @var MediaFileProcessorRegistryInterface
     */
    private $mediaFileProcessorRegistry;

    public function __construct(
        EntityRepositoryInterface $migrationRunRepo,
        MediaFileProcessorRegistryInterface $mediaFileProcessorRegistry
    ) {
        $this->migrationRunRepo = $migrationRunRepo;
        $this->mediaFileProcessorRegistry = $mediaFileProcessorRegistry;
    }

    /**
     * @param ProcessMediaMessage $message
     */
    public function handle($message): void
    {
        $context = $message->readContext();

        /* @var SwagMigrationRunEntity $run */
        $run = $this->migrationRunRepo->search(new Criteria([$message->getRunId()]), $context)->first();

        if ($run === null) {
            throw new EntityNotExistsException(SwagMigrationRunEntity::class, $message->getRunId());
        }

        if ($run->getConnection() === null) {
            throw new EntityNotExistsException(SwagMigrationConnectionEntity::class, $message->getRunId());
        }

        $migrationContext = new MigrationContext(
            $run->getConnection(),
            $message->getRunId()
        );

        $workload = [
            [
                'uuid' => $message->getMediaFileId(),
                'runId' => $message->getRunId(),
                'currentOffset' => 0,
                'state' => 'inProgress',
            ],
        ];

        $processor = $this->mediaFileProcessorRegistry->getProcessor($migrationContext);
        $processor->process($migrationContext, $context, $workload, $message->getFileChunkByteSize());
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ProcessMediaMessage::class,
        ];
    }
}
