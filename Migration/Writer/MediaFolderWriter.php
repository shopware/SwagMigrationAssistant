<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class MediaFolderWriter implements WriterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    public function __construct(EntityWriterInterface $entityWriter)
    {
        $this->entityWriter = $entityWriter;
    }

    public function supports(): string
    {
        return DefaultEntities::MEDIA_FOLDER;
    }

    public function writeData(array $data, Context $context): void
    {
        $this->entityWriter->upsert(
            MediaFolderDefinition::class,
            $data,
            WriteContext::createFromContext($context)
        );
    }
}
