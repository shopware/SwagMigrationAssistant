<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwagMigrationAssistant\Migration\Media\MediaFileProcessorRegistryInterface;
use SwagMigrationAssistant\Migration\Media\SwagMigrationMediaFileEntity;
use SwagMigrationAssistant\Migration\MessageQueue\Message\ProcessMediaMessage;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MediaFileProcessorService implements MediaFileProcessorServiceInterface
{
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

        foreach ($migrationData->getElements() as $mediaFile) {
            $message = new ProcessMediaMessage();
            /* @var SwagMigrationMediaFileEntity $mediaFile */
            $message->setMediaFileId($mediaFile->getMediaId());
            $message->setRunId($migrationContext->getRunUuid());
            $message->setFileChunkByteSize($fileChunkByteSize);
            $message->withContext($context);

            $this->messageBus->dispatch($message);
        }
    }
}
