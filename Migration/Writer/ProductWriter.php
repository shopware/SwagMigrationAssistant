<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\ORM\Write\EntityWriterInterface;
use Shopware\Core\Framework\ORM\Write\WriteContext;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;

class ProductWriter implements WriterInterface
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
        return ProductDefinition::getEntityName();
    }

    public function writeData(array $data, Context $context): void
    {
        foreach ($data as &$item) {
            foreach ($item['priceRules'] as &$priceRule) {
                $priceRule['rule']['payload'] = $this->structNormalizer->denormalize($priceRule['rule']['payload']);
            }
            unset($priceRule);
        }
        unset($item);

        $this->entityWriter->upsert(
            ProductDefinition::class,
            $data,
            WriteContext::createFromContext($context)
        );
    }
}
