<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Writer;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class ProductWriter implements WriterInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
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
        $this->productRepository->upsert($data, $context);
    }
}
