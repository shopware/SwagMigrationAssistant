<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Framework\Attribute\Aggregate\AttributeSet\AttributeSetDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;

class OrderAttributeWriter implements WriterInterface
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
        return DefaultEntities::ORDER_ATTRIBUTE;
    }

    public function writeData(array $data, Context $context): void
    {
        $this->entityWriter->upsert(
            AttributeSetDefinition::class,
            $data,
            WriteContext::createFromContext($context)
        );
    }
}
