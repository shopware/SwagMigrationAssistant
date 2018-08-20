<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Write\EntityWriterInterface;
use Shopware\Core\Framework\ORM\Write\WriteContext;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;

class OrderWriter implements WriterInterface
{
    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var StructNormalizer
     */
    private $structNormalizer;

    public function __construct(EntityWriterInterface $entityWriter, StructNormalizer $structNormalizer)
    {
        $this->entityWriter = $entityWriter;
        $this->structNormalizer = $structNormalizer;
    }

    public function supports(): string
    {
        return OrderDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        foreach ($data as &$item) {
            foreach ($item['transactions'] as &$transaction) {
                $transaction['amount'] = $this->structNormalizer->denormalize($transaction['amount']);
            }
            unset($transaction);
        }
        unset($item);

        $this->entityWriter->upsert(
            OrderDefinition::class,
            $data,
            WriteContext::createFromContext($context)
        );
    }
}
