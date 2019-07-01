<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
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

    public function __construct(
        EntityRepositoryInterface $mediaFileRepo,
        MessageBusInterface $messageBus
    ) {
        $this->mediaFileRepo = $mediaFileRepo;
        $this->messageBus = $messageBus;
    }

    public function processMediaFiles(MigrationContextInterface $migrationContext, Context $context, int $fileChunkByteSize): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $criteria->addFilter(new EqualsFilter('written', true));
        $criteria->setOffset($migrationContext->getOffset());
        $criteria->setLimit($migrationContext->getLimit());
        $criteria->addSorting(new FieldSorting('fileSize', FieldSorting::ASCENDING));
        $migrationData = $this->mediaFileRepo->search($criteria, $context);

        $currentCount = 0;
        $messageMediaUuids = [];
        /* @var SwagMigrationMediaFileEntity $mediaFile */
        foreach ($migrationData->getElements() as $mediaFile) {
            ++$currentCount;
            $messageMediaUuids[] = $mediaFile->getMediaId();

            if ($currentCount < self::MESSAGE_SIZE) {
                continue;
            }

            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $messageMediaUuids);
            $messageMediaUuids = [];
            $currentCount = 0;
        }

        if ($currentCount > 0) {
            $this->addMessageToBus($migrationContext->getRunUuid(), $context, $fileChunkByteSize, $messageMediaUuids);
        }
    }

    private function addMessageToBus(string $runUuid, Context $context, int $fileChunkByteSize, array $mediaUuids): void
    {
        $message = new ProcessMediaMessage();
        $message->setMediaFileIds($mediaUuids);
        $message->setRunId($runUuid);
        $message->setFileChunkByteSize($fileChunkByteSize);
        $message->withContext($context);
        $this->messageBus->dispatch($message);
    }
}
