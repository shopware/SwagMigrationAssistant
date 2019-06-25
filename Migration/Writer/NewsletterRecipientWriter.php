<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class NewsletterRecipientWriter implements WriterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var EntityDefinition
     */
    private $definition;

    public function __construct(EntityWriterInterface $entityWriter, EntityDefinition $definition)
    {
        $this->entityWriter = $entityWriter;
        $this->definition = $definition;
    }

    public function supports(): string
    {
        return DefaultEntities::NEWSLETTER_RECIPIENT;
    }

    public function writeData(array $data, Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data) {
            $this->entityWriter->upsert(
                $this->definition,
                $data,
                WriteContext::createFromContext($context)
            );
        });
    }
}
