<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Write\EntityWriterInterface;
use Shopware\Core\Framework\ORM\Write\WriteContext;

class CustomerWriter implements WriterInterface
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
        return CustomerDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        $this->entityWriter->upsert(
            CustomerDefinition::class,
            $data,
            WriteContext::createFromContext($context)
        );
    }
}
