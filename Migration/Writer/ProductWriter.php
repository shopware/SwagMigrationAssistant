<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\Serializer\StructNormalizer;

class ProductWriter implements WriterInterface
{
    /**
     * @var StructNormalizer
     */
    private $structNormalizer;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    public function __construct(EntityRepositoryInterface $productRepository, StructNormalizer $structNormalizer)
    {
        $this->productRepository = $productRepository;
        $this->structNormalizer = $structNormalizer;
    }

    public function supports(): string
    {
        return ProductDefinition::getEntityName();
    }

    /**
     * @param array[][] $data
     */
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

        $this->productRepository->upsert($data, $context);
    }

    /**
     * @param array[] $item
     */
    private function normalizeRule(array &$item): void
    {
        foreach ($item['priceRules'] as &$priceRule) {
            $priceRule['rule']['payload'] = $this->structNormalizer->denormalize($priceRule['rule']['payload']);
        }
        unset($priceRule);
    }
}
