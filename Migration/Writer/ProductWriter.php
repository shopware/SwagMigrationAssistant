<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Doctrine\DBAL\DBALException;
use Exception;
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
            if (isset($item['priceRules'])) {
                $this->normalizeRule($item);
            }

            if (isset($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $this->normalizeRule($child);
                }
                unset($child);
            }
        }
        unset($item);

        try {
            $this->entityWriter->upsert(
                ProductDefinition::class,
                $data,
                WriteContext::createFromContext($context)
            );
        } catch (Exception $e) {
            if ($e instanceof DBALException &&
                strpos($e->getMessage(), 'SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails') !== false &&
                strpos($e->getMessage(), 'CONSTRAINT `fk_product_category.category_id` FOREIGN KEY') !== false
            ) {
                throw new WriteProductException('As categories were imported before products, categories also have to be written first');
            }

            throw $e;
        }
    }

    private function normalizeRule(array &$item): void
    {
        foreach ($item['priceRules'] as &$priceRule) {
            $priceRule['rule']['payload'] = $this->structNormalizer->denormalize($priceRule['rule']['payload']);
        }
        unset($priceRule);
    }
}
