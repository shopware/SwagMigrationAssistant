<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class OrderDocumentAttributeWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::ORDER_DOCUMENT_CUSTOM_FIELD;
    }
}